<?php

namespace Larawiz\Larawiz\Lexing\Database;

use Faker\Generator;
use Illuminate\Support\Str;
use ReflectionMethod;

class Factory
{
    /**
     * Name of the soft-deleted state.
     *
     * @var string
     */
    public const SOFT_DELETED_STATE = 'deleted';

    /**
     * Saved password hash for all models.
     *
     * Using this we avoid generating the password for each model every time.
     *
     * @var string
     */
    protected $hashedPassword;

    /**
     * The password string to hash.
     *
     * @var string
     */
    protected $password;

    /**
     * A cached list of formatters.
     *
     * @var
     */
    protected $formatters;

    /**
     * Faker Generator.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Factory constructor.
     *
     * @param  \Faker\Generator  $faker
     * @param  string  $password
     */
    public function __construct(Generator $faker, string $password = 'secret')
    {
        $this->faker = $faker;
        $this->password = $password;
    }

    /**
     * Guesses which type or method it should call from Faker for the factory attributes.
     *
     * @param  string  $name
     * @param  string  $type
     * @return string
     * @throws \Illuminate\Contracts\Container\BindingResolutionException|\ReflectionException
     */
    public function guess(string $name, string $type)
    {
        // If it's a password, we return the static password string.
        if ($password = $this->returnPassword($name, $type)) {
            return $password;
        }

        // Use the faker's name guesser as basis to guess the correct value
        if ($guessed = $this->guessFakerName($name)) {
            return $guessed;
        }

        // We will try to get the correct formatter from Faker's providers.
        if ($formatter = $this->getFakerFormatter(Str::camel($name))) {
            return $formatter;
        }

        // Try to return an standard value from the type
        if ($defaultType = $this->returnDefaultFakerValue($type)) {
            return $defaultType;
        }

        // Everything has failed, so return an empty string and a to-do note.
        return "'', // TODO: Add a random generated value for the [{$name} ({$type})] property";
    }

    /**
     * Returns a password string.
     *
     * @param  string  $name
     * @param  string  $type
     * @return string|void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function returnPassword(string $name, string $type)
    {
        if ($name === 'password' && $type === 'string') {
            return $this->hashedPassword = $this->hashedPassword
                ?? "'" . app('hash')->make($this->password) . "'";
        }
    }

    /**
     * Guess the proper faker property based on the name.
     *
     * @param  string  $name
     * @return string
     */
    protected function guessFakerName(string $name)
    {

        switch (Str::of($name)->snake('')->lower()->__toString()) {
            case 'firstname':
                return "\$this->faker->firstName";
            case 'lastname':
                return "\$this->faker->lastName";
            case 'username':
            case 'name':
            case 'login':
                return "\$this->faker->userName";
            case 'email':
            case 'emailaddress':
                return "\$this->faker->email";
            case 'phonenumber':
            case 'phone':
            case 'telephone':
            case 'telnumber':
                return "\$this->faker->phoneNumber";
            case 'address':
                return "\$this->faker->address";
            case 'city':
            case 'town':
                return "\$this->faker->city";
            case 'street':
            case 'streetaddress':
                return "\$this->faker->streetAddress";
            case 'postcode':
            case 'zipcode':
                return "\$this->faker->postcode";
            case 'state':
            case 'county':
                return "\$this->faker->state";
            case 'country':
                return "\$this->faker->countryCode";
            case 'locale':
                return "\$this->faker->locale";
            case 'currency':
            case 'currencycode':
                return "\$this->faker->currencyCode";
            case 'url':
            case 'website':
                return "\$this->faker->url";
            case 'company':
            case 'companyname':
            case 'employer':
                return "\$this->faker->company";
            case 'title':
                return "\$this->faker->sentence";
            case 'body':
            case 'summary':
            case 'article':
            case 'description':
                return "\$this->faker->text";
        }
    }

    /**
     * Returns a default faker value based on the property type.
     *
     * @param string $type
     * @return string|void
     */
    protected function returnDefaultFakerValue(string $type)
    {
        switch ($type) {
            case 'boolean':
                return '$this->faker->boolean';
            case 'uuid':
                return '$this->faker->uuid';
            case 'date':
                return '$this->faker->date';
            case 'dateTime':
            case 'dateTimeTz':
                return '$this->faker->dateTime';
            case 'time':
            case 'timeTz':
                return '$this->faker->time';
            case 'year':
                return '$this->faker->year';
            case 'text':
            case 'mediumText':
            case 'longText':
                return '$this->faker->realText()';
            case 'integer':
            case 'unsignedInteger':
            case 'unsignedTinyInteger':
            case 'unsignedSmallInteger':
            case 'unsignedMediumInteger':
            case 'unsignedBigInteger':
                return '$this->faker->randomNumber()';
            case 'ipAddress':
                return '$this->faker->ipv4';
            case 'macAddress':
                return '$this->faker->macAddress';
            case 'float':
            case 'double':
            case 'decimal':
            case 'unsignedFloat':
            case 'unsignedDouble':
            case 'unsignedDecimal':
                return '$this->faker->randomFloat()';
        }
    }

    /**
     * Return the faker formatter string if it exists.
     *
     * @param  string  $formatter
     * @return string|void
     * @throws \ReflectionException
     */
    protected function getFakerFormatter(string $formatter)
    {
        // First we are gonna check if the formatter was already checked as valid.
        // If it is not, we are gonna cycle through each Faker providers to check
        // if the formatter exists and return the string the Factory should use.
        if (isset($this->formatters[$formatter])) {
            return $this->formatters[$formatter];
        }

        foreach ($this->faker->getProviders() as $provider) {
            if (method_exists($provider, $formatter)) {
                return $this->formatterString($formatter, (new ReflectionMethod($provider, $formatter)));
            }
        }
    }

    /**
     * Returns the correct formatter string from the Faker Provider.
     *
     * @param  string  $formatter
     * @param  \ReflectionMethod  $method
     * @return string
     */
    protected function formatterString(string $formatter, ReflectionMethod $method)
    {
        return $this->formatters[$formatter] = '$this->faker->'
            . $formatter
            . ($method->getNumberOfParameters() ? '()' : '');
    }
}
