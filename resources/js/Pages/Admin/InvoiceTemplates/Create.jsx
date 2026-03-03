// resources/js/Pages/Admin/InvoiceTemplates/Create.jsx
import { useForm } from '@inertiajs/react';
import InvoiceTemplateEditor from '@/Components/InvoiceTemplateEditor';
import { useState } from 'react';

export default function Create({ typeInvoices = [] }) {
  const [previewHTML, setPreviewHTML] = useState("");
  const { data, setData, post, errors } = useForm({
    name: '',
    type_invoice_id: typeInvoices?.[0]?.id ?? '',
    content: '',
    is_default: false,
  });

  const [loadingTemplate, setLoadingTemplate] = useState(false);

  const loadStarterTemplate = async () => {
    try {
      setLoadingTemplate(true);
      const selected = typeInvoices.find((type) => type.id === data.type_invoice_id);
      const typeSlug = selected?.slug ?? 'invoice';
      const templateSlug = ['std', 'wholesale'].includes(typeSlug) ? 'invoice' : typeSlug;
      const res = await fetch(`/templates/${templateSlug}.html`);
      console.log('res', res);
      if (!res.ok) throw new Error('Template not found');
      const html = await res.text();
      console.log('html', html);
      setData('content', html);
    } catch (err) {
      console.error('Failed to load template', err);
    } finally {
      setLoadingTemplate(false);
    }
  };

  const mockData = {
    logo: "<img src='/logo.png' alt='Logo' width='100'/>",
    site_name: "My Company",
    site_reg_no: "2025/123456/07",
    site_address: "123 Business St, East London",
    site_address_code: "5201",
    client_name: "John Doe",
    client_phone: "+27 82 123 4567",
    client_email: "john@example.com",
    invoice_number: "INV-1001",
    booking_ref: "BK-2025-001",
    invoice_date: "2025-09-24",
    balance_due: "R 1,250.00",
    service_name: "Mountain Bike Trail",
    service_adults: "2",
    service_children: "1",
    service_guides: "1",
    amount_ex_vat: "R 1086.96",
    vat: "R 163.04",
    amount_paid: "R 500.00",
    notes: "Thank you for your booking!",
    bank_account_owner: "My Company Pty Ltd",
    bank_account_no: "123456789",
    bank_account_type: "Business Cheque",
    bank_name: "FNB",
    bank_branch_code: "250655",
    lineItems: `
      <tr>
        <td>Mountain Bike</td>
        <td>2</td>
        <td>R 500</td>
        <td>R 1000</td>
      </tr>
    `,
    subtotal: "R 1000",
    tax: "R 150",
    grand_total: "R 1150",
  };

  const replacePlaceholders = (html) => {
    let output = html;
    Object.entries(mockData).forEach(([key, value]) => {
      output = output.replace(new RegExp(`{{${key}}}`, "g"), value);
    });
    return output;
  };
  return (
    <div className="flex flex-col items-center mt-10 justify-center">
      <form
        onSubmit={e => {
          e.preventDefault();
          post(route('admin.invoice-templates.store'));
        }}
      >
        <div className="mb-4">
          <label className="block">Template Name</label>
          <input
            type="text"
            className="input"
            value={data.name}
            onChange={e => setData('name', e.target.value)}
          />
          {errors.name && <p className="text-red-500 text-sm">{errors.name}</p>}
        </div>

        <div className="mb-4 flex items-center gap-2">
          <div className="flex-1">
            <label className="block">Type</label>
            <select
              className="input w-full"
              value={data.type_invoice_id}
              onChange={e => setData('type_invoice_id', Number(e.target.value))}
            >
              {typeInvoices.map((type) => (
                <option key={type.id} value={type.id}>{type.name}</option>
              ))}
            </select>
          </div>
          <button
            type="button"
            className="btn-secondary mt-6 border border-gray-300 rounded-lg px-4 py-2 text-sm"
            onClick={loadStarterTemplate}
            disabled={loadingTemplate}
          >
            {loadingTemplate ? 'Loading…' : 'Load Starter'}
          </button>
        </div>

        <InvoiceTemplateEditor
          content={data.content}
          handleChange={(field, value) => {
            setData(field, value);
            setPreviewHTML(replacePlaceholders(value));
          }}
          errors={errors}
        />
        <div className="border p-4 bg-gray-50">
          <h3 className="font-semibold mb-2">Preview</h3>
          <div
            className="preview-output"
            dangerouslySetInnerHTML={{ __html: previewHTML }}
          />
        </div>
        <label className="flex items-center gap-2 mt-4">
          <input
            type="checkbox"
            checked={data.is_default}
            onChange={e => setData('is_default', e.target.checked)}
          />
          Default
        </label>
        <button type="submit" className="btn-primary mt-4">
          Save
        </button>
      </form>
    </div>
  );
}
