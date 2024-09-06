<?php

namespace Dagstuhl\SwhArchiveClient\ApiClient\Internal;

use Dagstuhl\SwhArchiveClient\SwhObjects\SaveRequestStatus;
use Dagstuhl\SwhArchiveClient\SwhObjects\SaveTaskStatus;
use Carbon\Carbon;
use ReflectionClass;
use ReflectionProperty;

trait FillFromApiData
{
    /**
     * @param array $apiData
     *
     * maps api data to object properties and takes care of renaming (if necessary) and casting (date -> Carbon)
     */
    public function fillFromApiData(array $apiData): void
    {
        $reflect = new ReflectionClass(static::class);
        $props   = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach($props as $prop) {
            $type = $prop->getType();
            $prop = $prop->getName();
            $apiKey = static::$apiPropRenaming[$prop] ?? static::snakeCase($prop);

            $value = $apiData[$apiKey] ?? null;

            if (($type == Carbon::class || $type == '?'.Carbon::class) && $value !== null) {
                $value = Carbon::parse($value);
            }
            elseif ($type == SaveRequestStatus::class && is_string($value)) {
                $value = SaveRequestStatus::from($value);
            }
            elseif ($type == SaveTaskStatus::class && is_string($value)) {
                $value = SaveTaskStatus::from($value);
            }

            $this->{$prop} = $value;
        }
    }

    protected static function snakeCase($camelCaseString): string
    {
        $snakeCase = preg_replace_callback('/[A-Z]/', fn($matches) => '_' . strtolower($matches[0]), $camelCaseString);
        return ltrim($snakeCase, '_');
    }
}