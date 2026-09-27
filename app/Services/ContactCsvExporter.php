<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactCsvExporter
{
    private const BOM = "\xEF\xBB\xBF";

    private const HEADER = ['ID', '氏名', '性別', 'メール', '電話', '住所', '建物', 'カテゴリ', '内容', '作成日時'];

    public function download(Builder $query, string $fileName): StreamedResponse
    {
        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, self::BOM);
            fputcsv($handle, self::HEADER);

            foreach ($query->lazy() as $contact) {
                fputcsv($handle, $this->toRow($contact));
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<int, string|int|null>
     */
    private function toRow(Contact $contact): array
    {
        return [
            $contact->id,
            $contact->first_name.' '.$contact->last_name,
            $contact->gender_label,
            $contact->email,
            $contact->tel,
            $contact->address,
            $contact->building,
            $contact->category->content ?? '',
            $contact->detail,
            $contact->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
