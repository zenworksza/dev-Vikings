<?php

namespace App\Filament\Portal;

use App\Enums\DocumentType;
use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard\Step;

/**
 * The franchise application, as wizard steps. Answers are saved as JSON on
 * FranchiseeApplication::$data (encrypted at rest); uploads are handled
 * separately through App\Services\DocumentStorage.
 *
 * Modelled on the structure of a standard franchise application (applicant,
 * principals, financial position, experience/references, motivation,
 * documents, declaration) but written for Vikings. Deliberately omits bank
 * account numbers and balances — bank confirmations are uploaded instead.
 *
 * @return list<Step>
 */
class ApplicationForm
{
    /** @param  Closure(): void  $saveDraft  Called after each step validates. */
    public static function steps(Closure $saveDraft): array
    {
        return [
            self::applicantStep()->afterValidation($saveDraft),
            self::principalsStep()->afterValidation($saveDraft),
            self::financialStep()->afterValidation($saveDraft),
            self::experienceStep()->afterValidation($saveDraft),
            self::motivationStep()->afterValidation($saveDraft),
            self::documentsStep()->afterValidation($saveDraft),
            self::declarationStep(),
        ];
    }

    private static function applicantStep(): Step
    {
        return Step::make('You and your business')->schema([
            Select::make('entity_type')
                ->label('Nature of franchisee')
                ->options([
                    'sole_proprietorship' => 'Sole proprietorship',
                    'partnership' => 'Partnership',
                    'company' => 'Company (Pty) Ltd',
                    'close_corporation' => 'Close corporation',
                ])
                ->required()
                ->live(),
            TextInput::make('business_name')
                ->label('Registered business name')
                ->required(fn (Get $get) => filled($get('entity_type')) && $get('entity_type') !== 'sole_proprietorship')
                ->visible(fn (Get $get) => filled($get('entity_type')) && $get('entity_type') !== 'sole_proprietorship'),
            TextInput::make('phone_mobile')->label('Cellular number')->tel()->required(),
            TextInput::make('phone_business')->label('Business number')->tel(),
            Textarea::make('physical_address')->label('Physical address (including country)')->required()->rows(3),
            Textarea::make('postal_address')->label('Postal address')->rows(3),
            TextInput::make('heard_about')->label('How did you hear about this franchise opportunity?'),
            TextInput::make('current_employment')->label('Current business interests / employment'),
        ]);
    }

    private static function principalsStep(): Step
    {
        return Step::make('People')->schema([
            Text::make('Complete this for every partner, member or shareholder. If you are a sole proprietor, just yourself.'),
            Repeater::make('principals')
                ->label('Partners / members / shareholders')
                ->minItems(1)
                ->defaultItems(1)
                ->addActionLabel('Add another person')
                ->columns(2)
                ->schema([
                    TextInput::make('surname')->required(),
                    TextInput::make('first_names')->label('First name(s)')->required(),
                    DatePicker::make('date_of_birth')->required()->maxDate(now()),
                    TextInput::make('id_number')->label('ID number')->required(),
                    Select::make('marital_status')->options([
                        'single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed',
                    ]),
                    TextInput::make('dependents')->label('Number of dependents')->numeric()->minValue(0),
                    TextInput::make('nationality')->required(),
                    TextInput::make('phone')->label('Cellular number')->tel()->required(),
                    Textarea::make('physical_address')->rows(2)->columnSpanFull()->required(),
                    TextInput::make('years_at_address')->label('How long at this address?'),
                ]),
            Repeater::make('advisers')
                ->label('Bookkeeper / accounting officer / auditor (optional)')
                ->addActionLabel('Add adviser')
                ->columns(3)
                ->schema([
                    Select::make('role')->options([
                        'bookkeeper' => 'Bookkeeper', 'accounting_officer' => 'Accounting officer', 'auditor' => 'Auditor',
                    ])->required(),
                    TextInput::make('name')->required(),
                    TextInput::make('contact_number')->tel(),
                ]),
        ]);
    }

    private static function financialStep(): Step
    {
        return Step::make('Financial position')->schema([
            Text::make('Amounts in rand. You will upload a statement of assets and liabilities and proof of funds in the Documents step.'),
            TextInput::make('net_worth')->label('Net worth (per personal balance sheet)')->numeric()->prefix('R')->required(),
            TextInput::make('monthly_income')->label('Current monthly income')->numeric()->prefix('R')->required(),
            TextInput::make('monthly_expenditure')->label('Current monthly expenditure')->numeric()->prefix('R'),
            TextInput::make('cash_available')->label('Unencumbered cash available for investment')->numeric()->prefix('R')->required(),
            TextInput::make('total_assets')->numeric()->prefix('R'),
            TextInput::make('total_liabilities')->numeric()->prefix('R'),
            Repeater::make('finance_sources')
                ->label('Finance you intend to use (optional)')
                ->addActionLabel('Add finance source')
                ->columns(2)
                ->schema([
                    TextInput::make('institution')->label('Financing institution')->required(),
                    TextInput::make('nature')->label('Nature of finance'),
                    TextInput::make('amount')->numeric()->prefix('R'),
                    TextInput::make('monthly_repayment')->numeric()->prefix('R'),
                ]),
            Repeater::make('bankers')
                ->label('Present bankers')
                ->minItems(1)
                ->defaultItems(1)
                ->addActionLabel('Add bank')
                ->columns(3)
                ->schema([
                    TextInput::make('bank')->required(),
                    TextInput::make('branch'),
                    TextInput::make('account_type'),
                ]),
            Toggle::make('insolvent')->label('Have you ever been declared insolvent?')->live(),
            Toggle::make('rehabilitated')->label('Are you now rehabilitated?')
                ->visible(fn (Get $get) => (bool) $get('insolvent')),
        ]);
    }

    private static function experienceStep(): Step
    {
        return Step::make('Experience and references')->schema([
            Textarea::make('food_experience')->label('What experience do you have in the restaurant / food industry?')->required()->rows(4),
            Repeater::make('previous_businesses')
                ->label('Previous business interests / employment')
                ->addActionLabel('Add another')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Business / employer')->required(),
                    TextInput::make('period')->label('Period of involvement'),
                ]),
            Toggle::make('other_manager')->label('Will someone other than yourself manage the outlet?')->live(),
            TextInput::make('manager_name')->label('Manager’s name')
                ->visible(fn (Get $get) => (bool) $get('other_manager'))
                ->required(fn (Get $get) => (bool) $get('other_manager')),
            Textarea::make('manager_experience')->label('Manager’s restaurant / food-industry experience')
                ->visible(fn (Get $get) => (bool) $get('other_manager'))
                ->required(fn (Get $get) => (bool) $get('other_manager')),
            Repeater::make('personal_references')
                ->label('Personal references')
                ->minItems(2)
                ->defaultItems(2)
                ->addActionLabel('Add reference')
                ->columns(2)
                ->schema(self::referenceFields()),
            Repeater::make('trade_references')
                ->label('Trade references (optional)')
                ->addActionLabel('Add reference')
                ->columns(2)
                ->schema(self::referenceFields()),
        ]);
    }

    /** @return list<TextInput> */
    private static function referenceFields(): array
    {
        return [
            TextInput::make('name')->required(),
            TextInput::make('relationship')->required(),
            TextInput::make('address'),
            TextInput::make('contact_number')->tel()->required(),
        ];
    }

    private static function motivationStep(): Step
    {
        return Step::make('Motivation')->schema([
            TextInput::make('preferred_area')->label('Where would you like to open a Vikings?')->required(),
            Textarea::make('motivation')->label('Brief motivation for your franchise application')->required()->minLength(50)->rows(6),
            Textarea::make('personal_profile')->label('Brief personal profile: management philosophy, business and personal goals')->required()->rows(6),
            Textarea::make('comments')->rows(3),
        ]);
    }

    private static function documentsStep(): Step
    {
        $uploads = [];
        foreach (DocumentType::cases() as $type) {
            $uploads[] = FileUpload::make('uploads.'.$type->value)
                ->label($type->label())
                ->multiple()
                ->storeFiles(false) // App\Services\DocumentStorage stores them (encrypted S3) on save
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                ->maxSize(10 * 1024)
                ->maxFiles(5)
                ->helperText('PDF, JPG or PNG, up to 10 MB each. '.($type === DocumentType::PropertyLease || $type === DocumentType::BankConfirmation ? 'Optional now.' : 'Required to submit.'));
        }

        return Step::make('Documents')->schema([
            Text::make('Documents are encrypted and stored securely. Company registration is only required if you are not a sole proprietor.'),
            View::make('filament.portal.application-documents'),
            Section::make('Upload documents')->schema($uploads),
        ]);
    }

    private static function declarationStep(): Step
    {
        return Step::make('Declaration')->schema([
            Checkbox::make('accept_fees')
                ->label('If awarded a franchise, I undertake to pay the joining fee, the monthly royalty and advertising fees required by the Franchise Agreement, and any other required monies for developing the site where applicable.')
                ->accepted(),
            Checkbox::make('accept_declaration')
                ->label('I declare that the information in this application, and in my statement of assets and liabilities, is correct and fully discloses my assets and liabilities to the best of my knowledge and belief. I understand that I will be required to sign a comprehensive Franchise Agreement if accepted, and that submitting this application does not guarantee I will be granted a franchise.')
                ->accepted(),
            TextInput::make('signed_name')->label('Type your full name as your signature')->required(),
        ]);
    }
}
