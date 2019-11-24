<?php

namespace Cronboard\Tasks;

use Cronboard\Commands\MetadataExtractor as CommandMetadataExtractor;
use Illuminate\Support\Collection;

class MetadataExtractor extends CommandMetadataExtractor
{
    public function getMetadataFromObject($object)
    {
        if ($object instanceof Task) {
            $metadata = parent::getMetadataFromObject($object->getCommand());
            if (empty(array_filter($metadata))) {
                $metadata['name'] = $this->getNameFromTaskConstraints($object);
            }
            return $metadata;
        }
        return parent::getMetadataFromObject($object);
    }

    protected function getNameFromTaskConstraints(Task $task): ?string
    {
        return Collection::wrap($task->getConstraints())->map(function($constraint){
            if (in_array($constraint[0], ['name', 'description'])) {
                return $constraint[1][0];
            }
            return null;
        })->filter()->first();
    }
}
