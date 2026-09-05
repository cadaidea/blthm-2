<?php

namespace App\Filament\Support;


use App\Forms\Components\EditorJsField;
use Filament\Forms;

class Bloques
{
    /** Esquema de bloques reutilizable para Páginas, Home y Artículos. */
    public static function schema(): array
    {
        $full = Forms\Components\Toggle::make('full')->label('Ancho completo (full)')
            ->helperText('Imagen/color a borde de pantalla; el texto conserva margen.')->default(false);
        $radio = Forms\Components\TextInput::make('radio')->label('Redondeo de bordes (px)')
            ->numeric()->minValue(0)->default(0)->helperText('0 = recto (por defecto)');
        $alto = Forms\Components\TextInput::make('alto')->label('Alto fijo (px, opcional)')
            ->numeric()->minValue(0)->helperText('Vacío = automático. La imagen se recorta al alto.');
        $txt = Forms\Components\TextInput::make('texto_size')->label('Tamaño de texto (px)')
            ->numeric()->minValue(10)->default(18)->helperText('Por defecto 18.');
        $hsz = Forms\Components\TextInput::make('h_size')->label('Tamaño de título (px)')
            ->numeric()->minValue(12)->default(25)->helperText('Por defecto 25.');
        $talign = Forms\Components\Select::make('align')->label('Alineación del texto')
            ->options(['izq' => 'Izquierda', 'centro' => 'Centro', 'der' => 'Derecha'])->default('izq');

        return [
            Forms\Components\Builder\Block::make('titulo')->label('Título')->icon('heroicon-o-bars-3-bottom-left')->schema([
                Forms\Components\TextInput::make('titulo')->required(),
                Forms\Components\Select::make('nivel')->options(['h2' => 'Subtítulo (H2)', 'h3' => 'Sub-subtítulo (H3)'])->default('h2'),
                $hsz,
                $talign,
            ])->columns(2),
            Forms\Components\Builder\Block::make('texto')->label('Texto')->icon('heroicon-o-document-text')->schema([
                EditorJsField::make('texto')->label('Contenido')->columnSpanFull(),
                $txt,
                $talign,
                $full,
            ])->columns(2),
            Forms\Components\Builder\Block::make('imagen')->label('Imagen')->icon('heroicon-o-photo')->schema([
                Forms\Components\FileUpload::make('imagen')->image()->directory('paginas')->disk('public')->imageEditor()->required(),
                Forms\Components\TextInput::make('alt')->label('Texto alternativo'),
                Forms\Components\Select::make('ancho')->options(['completa' => 'Completa', 'grande' => 'Grande', 'mediana' => 'Mediana'])->default('completa'),
                Forms\Components\Select::make('align')->label('Alineación')->options(['izq' => 'Izquierda', 'centro' => 'Centro', 'der' => 'Derecha'])->default('centro'),
                Forms\Components\TextInput::make('ancho_px')->label('Ancho máx (px, opcional)')->numeric()->minValue(0),
                $alto,
                $radio,
                Forms\Components\TextInput::make('pie')->label('Pie de foto'),
                $full,
            ])->columns(2),
            Forms\Components\Builder\Block::make('imagen_texto')->label('Imagen + Texto (2 columnas)')->icon('heroicon-o-view-columns')->schema([
                Forms\Components\FileUpload::make('imagen')->image()->directory('paginas')->disk('public')->required(),
                Forms\Components\Select::make('posicion')->label('Imagen a la')->options(['izq' => 'Izquierda', 'der' => 'Derecha'])->default('izq'),
                $alto,
                $radio,
                $txt,
                Forms\Components\RichEditor::make('texto')->columnSpanFull()->toolbarButtons(['bold','italic','link','bulletList','h3']),
                $full,
            ])->columns(2),
            Forms\Components\Builder\Block::make('imagen_borde')->label('Imagen al borde + Texto')->icon('heroicon-o-rectangle-group')->schema([
                Forms\Components\FileUpload::make('imagen')->label('Imagen (sangra al borde de pantalla)')->image()->directory('paginas')->disk('public')->required(),
                Forms\Components\Select::make('posicion')->label('Imagen a la')->options(['izq' => 'Izquierda', 'der' => 'Derecha'])->default('izq'),
                Forms\Components\TextInput::make('titulo')->label('Título (opcional)'),
                $hsz,
                $txt,
                Forms\Components\RichEditor::make('texto')->label('Texto')->columnSpanFull()->toolbarButtons(['bold','italic','link','bulletList','h3']),
                $alto,
                $radio,
            ])->columns(2),
            Forms\Components\Builder\Block::make('galeria')->label('Galería de imágenes (encabezado + cuadradas)')->icon('heroicon-o-squares-2x2')->schema([
                Forms\Components\TextInput::make('eyebrow')->label('Antetítulo (opcional)'),
                Forms\Components\RichEditor::make('titulo')->label('Encabezado')->toolbarButtons(['bold','italic']),
                $hsz,
                $radio,
                $full,
                Forms\Components\Repeater::make('items')->label('Imágenes (1-4, se muestran cuadradas)')->schema([
                    Forms\Components\FileUpload::make('imagen')->image()->directory('paginas')->disk('public')->required(),
                    Forms\Components\TextInput::make('alt')->label('Texto alternativo'),
                ])->defaultItems(3)->maxItems(4)->addActionLabel('Agregar imagen')->reorderable()->collapsed()->grid(2),
            ]),
            Forms\Components\Builder\Block::make('video')->label('Video de fondo (YouTube/Vimeo/Dailymotion)')->icon('heroicon-o-play-circle')->schema([
                Forms\Components\Select::make('proveedor')->label('Proveedor')->options(['youtube' => 'YouTube', 'vimeo' => 'Vimeo', 'dailymotion' => 'Dailymotion'])->default('youtube')->required(),
                Forms\Components\TextInput::make('video_url')->label('URL o ID del video')->required()->columnSpanFull(),
                Forms\Components\TextInput::make('titulo')->label('Título (opcional)'),
                Forms\Components\TextInput::make('subtitulo')->label('Subtítulo (opcional)'),
                Forms\Components\Textarea::make('texto')->label('Texto (opcional)')->rows(2)->columnSpanFull(),
                Forms\Components\TextInput::make('b1_texto')->label('Botón 1 — texto'),
                Forms\Components\TextInput::make('b1_url')->label('Botón 1 — URL'),
                Forms\Components\TextInput::make('b2_texto')->label('Botón 2 — texto'),
                Forms\Components\TextInput::make('b2_url')->label('Botón 2 — URL'),
                Forms\Components\Select::make('tono')->label('Color del texto')->options(['claro' => 'Claro', 'oscuro' => 'Oscuro'])->default('claro'),
                Forms\Components\Select::make('pos_h')->label('Contenido horizontal')->options(['izq' => 'Izquierda', 'centro' => 'Centro', 'der' => 'Derecha'])->default('centro'),
                Forms\Components\Select::make('pos_v')->label('Contenido vertical')->options(['arriba' => 'Arriba', 'centro' => 'Centro', 'abajo' => 'Abajo'])->default('centro'),
                $alto,
                $radio,
                $full,
            ])->columns(2),
            Forms\Components\Builder\Block::make('slider')->label('Slider / Carrusel')->icon('heroicon-o-rectangle-stack')->schema([
                Forms\Components\TextInput::make('intervalo')->label('Autoplay (segundos, 0 = manual)')->numeric()->minValue(0)->default(5),
                $alto,
                $radio,
                $full,
                Forms\Components\Repeater::make('slides')->label('Diapositivas')->schema([
                    Forms\Components\FileUpload::make('imagen')->label('Imagen de fondo')->image()->directory('paginas')->disk('public')->required(),
                    Forms\Components\Select::make('tono')->label('Color del texto')->options(['claro' => 'Claro', 'oscuro' => 'Oscuro'])->default('claro'),
                    Forms\Components\TextInput::make('titulo')->label('Título'),
                    Forms\Components\TextInput::make('subtitulo')->label('Subtítulo'),
                    Forms\Components\Textarea::make('texto')->label('Texto')->rows(2)->columnSpanFull(),
                    Forms\Components\TextInput::make('b1_texto')->label('Botón 1 — texto'),
                    Forms\Components\TextInput::make('b1_url')->label('Botón 1 — URL'),
                    Forms\Components\TextInput::make('b2_texto')->label('Botón 2 — texto'),
                    Forms\Components\TextInput::make('b2_url')->label('Botón 2 — URL'),
                    Forms\Components\Select::make('pos_h')->label('Contenido horizontal')->options(['izq' => 'Izquierda', 'centro' => 'Centro', 'der' => 'Derecha'])->default('izq'),
                    Forms\Components\Select::make('pos_v')->label('Contenido vertical')->options(['arriba' => 'Arriba', 'centro' => 'Centro', 'abajo' => 'Abajo'])->default('abajo'),
                ])->defaultItems(2)->minItems(1)->addActionLabel('Agregar diapositiva')->reorderable()->collapsed()->columns(2),
            ]),
            Forms\Components\Builder\Block::make('productos')->label('Productos recomendados (por categoría)')->icon('heroicon-o-shopping-bag')->schema([
                Forms\Components\TextInput::make('titulo')->label('Título (opcional)'),
                Forms\Components\TextInput::make('limite')->label('Máximo a mostrar')->numeric()->minValue(1)->default(6),
                Forms\Components\Select::make('categoria_id')->label('Categoría')->options(\App\Models\Categoria::where('activo', true)->pluck('nombre', 'id'))->searchable(),
                Forms\Components\Select::make('productos')->label('Productos específicos (opcional)')->multiple()->options(\App\Models\Producto::where('activo', true)->pluck('nombre', 'id'))->searchable()->helperText('Si eliges productos aquí, se ignora la categoría.'),
            ])->columns(2),
            Forms\Components\Builder\Block::make('tabla')->label('Tabla')->icon('heroicon-o-table-cells')->schema([
                Forms\Components\Textarea::make('contenido')->rows(6)->required()->helperText('Una fila por línea. Columnas separadas con |. La primera fila es encabezado.'),
                $full,
            ]),
            Forms\Components\Builder\Block::make('cita')->label('Cita')->icon('heroicon-o-chat-bubble-bottom-center-text')->schema([
                Forms\Components\Textarea::make('texto')->required()->rows(3),
                $txt,
                $full,
            ])->columns(2),
            Forms\Components\Builder\Block::make('columnas')->label('Columnas (lado a lado)')->icon('heroicon-o-view-columns')->schema([
                Forms\Components\Select::make('cantidad')->label('N° de columnas')->options(['2' => '2', '3' => '3', '4' => '4'])->default('2'),
                $txt,
                $radio,
                $full,
                Forms\Components\Repeater::make('items')->label('Columnas')->schema([
                    Forms\Components\FileUpload::make('imagen')->label('Imagen (opcional)')->image()->directory('paginas')->disk('public'),
                    Forms\Components\RichEditor::make('texto')->label('Texto')->toolbarButtons(['bold','italic','link','bulletList','h3']),
                ])->defaultItems(2)->maxItems(4)->addActionLabel('Agregar columna')->reorderable()->collapsed(),
            ]),
            Forms\Components\Builder\Block::make('texto_imagen')->label('Texto + Imagen (con color)')->icon('heroicon-o-photo')->schema([
                Forms\Components\FileUpload::make('imagen')->label('Imagen')->image()->directory('paginas')->disk('public'),
                Forms\Components\Select::make('posicion')->label('Imagen a la')->options(['izq' => 'Izquierda', 'der' => 'Derecha'])->default('izq'),
                $alto,
                $radio,
                $txt,
                Forms\Components\RichEditor::make('texto')->label('Texto')->columnSpanFull()->toolbarButtons(['bold','italic','link','bulletList','h3']),
                Forms\Components\ColorPicker::make('fondo')->label('Color de fondo (opcional)'),
                $full,
            ])->columns(2),
            Forms\Components\Builder\Block::make('hero')->label('Texto sobre fondo (imagen o color)')->icon('heroicon-o-rectangle-group')->schema([
                Forms\Components\TextInput::make('titulo')->label('Título'),
                Forms\Components\Textarea::make('texto')->label('Texto')->rows(2)->columnSpanFull(),
                $hsz,
                $txt,
                Forms\Components\TextInput::make('boton_texto')->label('Texto del botón (opcional)'),
                Forms\Components\TextInput::make('boton_url')->label('URL del botón'),
                Forms\Components\FileUpload::make('imagen')->label('Imagen de fondo (opcional)')->image()->directory('paginas')->disk('public'),
                Forms\Components\ColorPicker::make('fondo')->label('Color de fondo (si no hay imagen)'),
                Forms\Components\Select::make('tono')->label('Color del texto')->options(['claro' => 'Claro (fondo oscuro)', 'oscuro' => 'Oscuro (fondo claro)'])->default('claro'),
                Forms\Components\Select::make('pos_h')->label('Contenido horizontal')->options(['izq' => 'Izquierda', 'centro' => 'Centro', 'der' => 'Derecha'])->default('izq'),
                Forms\Components\Select::make('pos_v')->label('Contenido vertical')->options(['arriba' => 'Arriba', 'centro' => 'Centro', 'abajo' => 'Abajo'])->default('abajo'),
                $alto,
                $radio,
                $full,
            ])->columns(2),
            Forms\Components\Builder\Block::make('formulario_contacto')->label('Formulario de contacto')->icon('heroicon-o-envelope')->schema([
                Forms\Components\Select::make('formulario_slug')->label('Formulario')
                    ->options(fn () => \App\Models\FormularioContacto::where('activo', true)->pluck('nombre', 'slug'))
                    ->required()->helperText('Elige el formulario que se mostrará aquí (gestionado en Marketing → Formularios de contacto).'),
            ]),
            Forms\Components\Builder\Block::make('botones')->label('Botones / CTAs (1-3)')->icon('heroicon-o-cursor-arrow-rays')->schema([
                $full,
                Forms\Components\Repeater::make('items')->label('Botones')->columns(2)->schema([
                    Forms\Components\TextInput::make('titulo')->label('Título'),
                    Forms\Components\TextInput::make('texto')->label('Subtexto'),
                    Forms\Components\TextInput::make('boton_texto')->label('Texto botón'),
                    Forms\Components\TextInput::make('url')->label('URL'),
                ])->defaultItems(1)->maxItems(3)->addActionLabel('Agregar botón')->reorderable()->collapsed(),
            ]),
        ];
    }
}
