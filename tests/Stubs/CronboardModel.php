<?php

namespace Cronboard\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class CronboardModel extends Model
{
	protected $fillable = [
		'id'
	];

    public static function find($id, $columns = ['*'])
    {
        return new static(compact('id'));
    }

    public static function findOrFail($id, $columns = ['*'])
    {
        return new static(compact('id'));
    }
}
