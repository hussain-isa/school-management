<?php
namespace App\Enums;

enum relationType: string
{
    case FATHER = 'Father';
    case MOTHER = 'Mother';
    case SISTER = 'Sister';
    case BROTHER = 'Brother';

    public static function getValues(): array
    {
        return array_column(relationType::cases(), 'value');
    }

    public static function getKeyValues(): array
    {
        return array_column(relationType::cases(), 'value', index_key:'value');
    }
}