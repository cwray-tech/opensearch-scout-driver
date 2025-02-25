<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriver\Factories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use OpenSearch\Adapter\Documents\Document;
use UnexpectedValueException;

class DocumentFactory implements DocumentFactoryInterface
{
    public function makeFromModels(Collection $models): Collection
    {
        return $models->map(static function (Model $model) {
            if (
                config('scout.soft_delete', false) &&
                in_array(SoftDeletes::class, class_uses_recursive(get_class($model)), true)
            ) {
                $model->pushSoftDeleteMetadata();
            }

            $documentId = (string)$model->getScoutKey();
            $documentContent = array_merge($model->scoutMetadata(), $model->toSearchableArray());

            if (array_key_exists('_id', $documentContent)) {
                throw new UnexpectedValueException(sprintf(
                    '_id is not allowed in the document content. Please, make sure the field is not returned by ' .
                    'the %1$s::toSearchableArray or %1$s::scoutMetadata methods.',
                    class_basename($model)
                ));
            }

            return new Document($documentId, $documentContent);
        });
    }
}
