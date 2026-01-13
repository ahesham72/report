<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\OrdersImport;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column; // تسهيل كتابة الأعمدة
use Carbon\Carbon;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. زر التصدير المعدل والمطور
            ExportAction::make()
                ->label('تصدير الكل')
                ->color('info')
                ->icon('heroicon-o-arrow-down-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromModel()
                        // حذف أعمدة Created At و Updated At تماماً
                        ->except([
                            'created_at',
                            'updated_at',
                        ])
                        ->withColumns([
                            Column::make('department_name')->heading('كود الإدارة'),
                            Column::make('order_no')->heading('رقم الطلب'),
                            Column::make('order_date')->heading('تاريخ الطلب'),
                            Column::make('approval_date')->heading('تاريخ الموافقة'),
                            Column::make('po_no')->heading('رقم أمر التوريد'),
                            Column::make('po_date')->heading('تاريخ أمر التوريد'),

                            // إضافة عمود مدة التوريد المحسوب (الفرق بين التواريخ)
                            Column::make('lead_time')
                                ->heading('مدة التوريد (بالأيام)')
                                ->getStateUsing(fn ($record) =>
                                    ($record->po_date && $record->approval_date)
                                    ? Carbon::parse($record->approval_date)->diffInDays(Carbon::parse($record->po_date))
                                    : '—'
                                ),
                        ]),
                ]),

            // 2. زر الاستيراد (كما هو دون تعديل)
            Actions\Action::make('import')
                ->label('استيراد (Import)')
                ->color('success')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('attachment')
                        ->label('اختر ملف الإكسيل')
                        ->disk('public')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $filePath = storage_path('app/public/' . $data['attachment']);
                    try {
                        Excel::import(new OrdersImport, $filePath);
                        \Filament\Notifications\Notification::make()->title('تم الاستيراد بنجاح')->success()->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()->title('خطأ')->body($e->getMessage())->danger()->send();
                    }
                }),

            Actions\CreateAction::make()->label('إضافة طلب جديد'),
        ];
    }
}
