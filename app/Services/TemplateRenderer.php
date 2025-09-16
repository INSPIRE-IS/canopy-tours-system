<?php

namespace App\Services;

class TemplateRenderer
{
    /**
     * Render a template by replacing {{placeholders}} with data.
     */
    public function render(string $template, array $data): string
    {
        // Handle line items specially
        if (isset($data['items']) && is_array($data['items'])) {
            $template = str_replace('{{line_items}}', $this->renderLineItems($data['items']), $template);
        }
        // Handle line items block
        if (preg_match('/{{line_items:start}}(.*?){{line_items:end}}/s', $template, $matches)) {
            $rowTemplate = $matches[1];
            $rows = $this->renderBlockLineItems($rowTemplate, $data['items'] ?? []);
            $template = str_replace($matches[0], $rows, $template);
        }
        // Replace simple placeholders
        $placeholders = $this->buildPlaceholders($data);

        return strtr($template, $placeholders);
    }

    /**
     * Convert nested array data into {{key}} placeholders.
     * Example: ['site' => ['name' => 'X']] → ['{{site_name}}' => 'X']
     */
    protected function buildPlaceholders(array $data, string $prefix = ''): array
    {
        $placeholders = [];

        foreach ($data as $key => $value) {
            $fullKey = $prefix ? $prefix . '_' . $key : $key;

            if (is_array($value)) {
                $placeholders += $this->buildPlaceholders($value, $fullKey);
            } else {
                $placeholders['{{' . $fullKey . '}}'] = $value ?? '';
            }
        }

        return $placeholders;
    }

    protected function renderLineItems(array $items): string
    {
        $rows = [];

        foreach ($items as $i => $item) {
            $rows[] = sprintf(
                '<tr>
                    <td style="font-size: 8pt;">%d</td>
                    <td style="font-size: 8pt;">%s</td>
                    <td style="font-size: 8pt;">%s</td>
                    <td style="font-size: 8pt;">%s</td>
                    <td style="font-size: 8pt;">%s</td>
                </tr>',
                $i + 1,
                e($item['description'] ?? ''),
                e($item['qty'] ?? ''),
                e($item['rate'] ?? ''),
                e($item['subtotal'] ?? '')
            );
        }

        return implode("\n", $rows);
    }


    protected function renderBlockLineItems(string $rowTemplate, array $items): string
    {
        $rows = [];

        foreach ($items as $i => $item) {
            $row = strtr($rowTemplate, [
                '{{line_no}}'   => $i + 1,
                '{{item_desc}}' => $item['description'] ?? '',
                '{{item_qty}}'  => $item['qty'] ?? '',
                '{{item_rate}}' => $item['rate'] ?? '',
                '{{item_price}}' => $item['subtotal'] ?? '',
            ]);
            $rows[] = $row;
        }

        return implode("\n", $rows);
    }
}
