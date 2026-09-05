<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSuscriptor extends Model
{
    protected $table = 'newsletter_suscriptores';
    protected $fillable = ['email'];
}
