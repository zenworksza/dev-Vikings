<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\PageStatus;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Each Builder block's key must exactly match a Blade component file under
 * resources/views/components/blocks/ (e.g. 'rich-text' ->
 * blocks/rich-text.blade.php) — see App\Http\Controllers\PageController,
 * which resolves sections to blocks by that convention.
 */
class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Content')
                    ->columns(2)
                    ->components([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        TextInput::make('slug')
                            ->maxLength(255)
                            ->helperText('Leave blank to generate from the title. Changing this after publishing breaks existing links.')
                            ->columnSpan(1),
                        Select::make('status')
                            ->options(collect(PageStatus::cases())->mapWithKeys(fn (PageStatus $status) => [$status->value => $status->label()]))
                            ->default(PageStatus::Draft->value)
                            ->required()
                            ->columnSpan(1),
                    ]),

                Section::make('SEO')
                    ->collapsed()
                    ->components([
                        TextInput::make('meta_title')
                            ->maxLength(255)
                            ->helperText('Falls back to the page title if left blank.'),
                        Textarea::make('meta_description')
                            ->maxLength(500)
                            ->rows(3),
                        SpatieMediaLibraryFileUpload::make('featured_image')
                            ->collection('featured_image')
                            ->image()
                            ->helperText('Used for social-share previews.'),
                    ]),

                Builder::make('sections')
                    ->label('Page sections')
                    ->blocks([
                        Block::make('hero')
                            ->label('Hero')
                            ->icon(Heroicon::OutlinedRectangleGroup)
                            ->schema([
                                TextInput::make('eyebrow')->maxLength(255),
                                TextInput::make('headline')->required()->maxLength(255),
                                Textarea::make('subtext')->rows(2),
                                TextInput::make('primary_cta_label')->label('Primary button label')->maxLength(255),
                                TextInput::make('primary_cta_url')->label('Primary button URL')->url(),
                                TextInput::make('secondary_cta_label')->label('Secondary link label')->maxLength(255),
                                TextInput::make('secondary_cta_url')->label('Secondary link URL')->url(),
                            ]),
                        Block::make('rich-text')
                            ->label('Rich text')
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->schema([
                                RichEditor::make('content'),
                            ]),
                        Block::make('call-to-action')
                            ->label('Call to action')
                            ->icon(Heroicon::OutlinedMegaphone)
                            ->schema([
                                TextInput::make('heading')->required()->maxLength(255),
                                Textarea::make('subtext')->rows(2),
                                TextInput::make('button_label')->maxLength(255),
                                TextInput::make('button_url')->url(),
                            ]),
                    ])
                    ->addActionLabel('Add section')
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }
}
