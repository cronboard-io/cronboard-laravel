<?php

namespace Cronboard\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CronboardModel extends Model
{
    const MISSING_ID = 'missing';

	protected $fillable = [
		'id'
	];

    public static function find($id, $columns = ['*'])
    {
        return new static(compact('id'));
    }

    public static function findOrFail($id, $columns = ['*'])
    {
        if ($id === static::MISSING_ID) {
            throw (new ModelNotFoundException)->setModel(static::class);
        }
        return new static(compact('id'));
    }
}
