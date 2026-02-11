<?php

namespace App\Imports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\ToModel;
use Carbon\Carbon;

class POUpdateImporter implements ToModel
{
    // متغيرات لحفظ القيم مؤقتاً لأنها تأتي في أسطر مختلفة
    private $lastOrderNo = null;
    private $lastPoNo = null;

    public function model(array $row)
    {
        // تنظيف السطر من الفراغات
        $col0 = trim((string)($row[0] ?? ''));
        $col1 = trim((string)($row[1] ?? ''));

        // 1. إذا وجدنا سطر القيم (رقم التوريد في العمود 0 ورقم الشراء في العمود 1)
        if (is_numeric($col0) && is_numeric($col1) && !str_contains($col0, '.')) {
            $this->lastPoNo = $col0;
            $this->lastOrderNo = $col1;
            return null;
        }

        // 2. إذا وجدنا سطر التاريخ (يظهر فيه رقم عشري طويل مثل 46004.0000000001)
        if (is_numeric($col0) && str_contains($col0, '.') && $this->lastOrderNo) {
            $poDate = $this->parseDate($col0);

            // الآن لدينا (رقم الطلب + رقم التوريد + التاريخ) .. نقوم بالتحديث
            $order = Order::where('order_no', $this->lastOrderNo)->first();

            if ($order) {
                $order->update([
                    'po_no'   => $this->lastPoNo,
                    'po_date' => $poDate,
                ]);
            }

            // تصفير المتغيرات للسطر القادم
            $this->lastOrderNo = null;
            $this->lastPoNo = null;
        }

        return null;
    }

    private function parseDate($val) {
        if (empty($val)) return null;
        try {
            // تحويل رقم الإكسيل العشري إلى تاريخ حقيقي
            if (is_numeric($val)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val))->format('Y-m-d');
            }
            return Carbon::parse($val)->format('Y-m-d');
        } catch (\Exception $e) { return null; }
    }
}