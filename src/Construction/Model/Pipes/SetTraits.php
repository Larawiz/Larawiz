<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Illuminate\Support\Arr;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetTraits
{
    /**
     * Handle the model construction.
     *
     * @param  \Larawiz\Larawiz\Construction\Model\ModelConstruction  $construction
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(ModelConstruction $construction, Closure $next)
    {
        if ($construction->model->quickTraits->isNotEmpty()) {
            foreach ($construction->model->quickTraits as $trait) {

                $namespace = $trait->external ? $trait->namespace : $trait->fullNamespace();

                Arr::first($construction->file->getNamespaces())->addUse($namespace);

                $construction->class->addTrait($namespace);
            }
        }

        return $next($construction);
    }
}
