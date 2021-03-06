<?php

namespace Larawiz\Larawiz\Construction\Model;

use Illuminate\Pipeline\Pipeline;

class ModelConstructorPipeline extends Pipeline
{
    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [
        Pipes\CreateModelInstance::class,
        Pipes\SetTableName::class,
        Pipes\SetPerPage::class,
        Pipes\SetColumnCasting::class,
        Pipes\SetFillable::class,
        Pipes\SetHidden::class,
        Pipes\SetAppend::class,
        Pipes\SetRelations::class,
        Pipes\SetEagerLoads::class,
        Pipes\SetPrimaryKey::class,
        Pipes\SetRouteBinding::class,
        Pipes\SetColumnComments::class,
        Pipes\SetTimestamp::class,
        Pipes\SetSoftDeletes::class,
        Pipes\SetPasswordMutator::class,
        Pipes\SetTraits::class,
        Pipes\SetsFactoryTrait::class,
        Pipes\SetsLocalScopes::class,
        Pipes\WriteUuidTrait::class,
        Pipes\WriteModel::class,
        Pipes\WriteObserver::class,
        Pipes\WriteGlobalScopes::class,
        Pipes\WriteSeeder::class,
        Pipes\WriteTraits::class,
        Pipes\WriteCasts::class,
    ];
}
