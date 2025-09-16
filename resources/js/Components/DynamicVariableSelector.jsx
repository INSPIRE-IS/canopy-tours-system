// resources/js/Components/DynamicVariableSelector.jsx
export default function DynamicVariableSelector({ onInsert }) {
  const variables = [
    { key: 'logo', label: 'Logo' },
    { key: 'site_name', label: 'Site Name' },
    { key: 'site_address', label: 'Site Address' },
    { key: 'site_address_code', label: 'Site Address Code' },
    { key: 'site_phone', label: 'Site Phone' },
    { key: 'site_RegNo', label: 'Site RegNo' },
    { key: 'site_email', label: 'Site Email' },
    { key: 'bank_account_owner', label: 'Bank Account Owner' },
    { key: 'bank_account_no', label: 'Bank Account No' },    
    { key: 'bank_account_type', label: 'Bank Account Type' },
    { key: 'bank_name', label: 'Bank Name' },
    { key: 'branch_code', label: 'Branch Code' },

    { key: 'client_name', label: 'Client Name' },
    { key: 'client_phone', label: 'Client Phone' },
    { key: 'client_email', label: 'Client Email' },
    { key: 'client_address', label: 'Client Address' },

    { key: 'invoice_no', label: 'Invoice No' },
    { key: 'booking_ref', label: 'Booking Ref' },
    { key: 'invoice_date', label: 'Invoice Date' },

    { key: 'service_name', label: 'Service Name' },
    { key: 'adults', label: 'Adults' },
    { key: 'children', label: 'Children' },
    { key: 'guides', label: 'Guides' },
    { key: 'amount_ex_vat', label: 'Amount Ex VAT' },
    { key: 'vat', label: 'VAT' },
    { key: 'amount_paid', label: 'Amount Paid' },
    { key: 'balance_due', label: 'Balance Due' },
    { key: 'fee_table', label: 'Fees Table' },
    { key: 'notes', label: 'Notes' },
    { key: 'line_items', label: 'Line Items' },
  ];

  return (
    <div className="flex gap-2 flex-wrap mb-4">
      {variables.map((v) => (
        <button
          type="button"
          key={v.key}
          className="px-2 py-1 text-sm bg-gray-200 rounded hover:bg-gray-300"
          onClick={() => onInsert(`{{${v.key}}}`)}
        >
          {v.label}
        </button>
      ))}
    </div>
  );
}
