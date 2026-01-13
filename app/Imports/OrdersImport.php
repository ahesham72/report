<?php

namespace App\Imports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\ToModel;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class OrdersImport implements ToModel
{
    private $poMap = [];
    private $adminCode = 'N/A';
    private $lastValidDateA = null;
    private $lastValidDateB = null;

    public function model(array $row)
    {
        // 1. تنظيف البيانات
        $colA = $row[0] ?? null;
        $colB = $row[1] ?? null;
        $colC = trim((string)($row[2] ?? ''));
        $colD = strtolower(trim((string)($row[3] ?? '')));
        $colE = trim((string)($row[4] ?? ''));
        $colF = trim((string)($row[5] ?? ''));

        // 2. تحديث التواريخ الاحتياطية (لحل مشكلة الخلايا المدمجة)
        if ($this->isAnyDate($colA)) $this->lastValidDateA = $colA;
        if ($this->isAnyDate($colB)) $this->lastValidDateB = $colB;

        // 3. التقاط كود الإدارة والـ PoNo
        if ($colD === 'code' && !empty($colC)) {
            $this->adminCode = $colC;
        }
        if (!empty($colF) && is_numeric($colF) && !empty($colE)) {
            $this->poMap[$colF] = $colE;
        }

        // 4. التنفيذ عند وجود رقم طلب (numReq)
        if (!empty($colC) && is_numeric($colC) && $colD !== 'code') {

            // محاولة جلب التاريخ الحالي، وإذا فشل نأخذ آخر تاريخ صالح تم رصده فوقه
            $dateA = $this->forceConvertDate($colA ?: $this->lastValidDateA);
            $dateB = $this->forceConvertDate($colB ?: $this->lastValidDateB);

            return new Order([
                'department_name' => $this->adminCode,
                'order_no'        => $colC,
                'order_date'      => $dateB ?: $dateA,
                'approval_date'   => $dateA ?: $dateB,
                'po_no'           => $this->poMap[$colC] ?? null,
                'po_date'         => null,
            ]);
        }

        return null;
    }

    private function isAnyDate($value) {
        if (!$value) return false;
        return is_numeric($value) || preg_match('/\d/', (string)$value);
    }

    private function forceConvertDate($value) {
        if (!$value) return null;

        try {
            // الحالة 1: رقم إكسيل
            if (is_numeric($value) && $value > 40000) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('Y-m-d');
            }

            // الحالة 2: نص يحتوي على تاريخ (مثل 01/01/2024 أو 2024-01-01)
            $clean = str_replace(['/', '.', ' '], '-', trim((string)$value));
            if (preg_match('/\d{1,4}-\d{1,2}-\d{1,4}/', $clean)) {
                return Carbon::parse($clean)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }
}
