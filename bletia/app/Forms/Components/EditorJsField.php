<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class EditorJsField extends Field
{
    protected string $view = 'forms.components.editorjs-field';

    protected function setUp(): void
    {
        parent::setUp();
        $this->dehydrateStateUsing(function ($state) {
            if (is_string($state)) {
                $decoded = json_decode($state, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
            }
            return $state;
        });
    }
}
