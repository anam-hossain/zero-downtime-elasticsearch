<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'country';

    /**
     * Get the cities for the country.
     */
    public function cities()
    {
        return $this->hasMany(City::class, 'CountryCode', 'Code');
    }

    /**
     * Get the languages for the country.
     */
    public function languages()
    {
        return $this->hasMany(Language::class, 'CountryCode', 'Code');
    }
}
