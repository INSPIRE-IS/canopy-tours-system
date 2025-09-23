// resources/js/Pages/Admin/InvoiceTemplates/Create.jsx
import { useForm } from '@inertiajs/react';
import InvoiceTemplateEditor from '@/Components/InvoiceTemplateEditor';
import { useState } from 'react';

export default function Create() {
  const { data, setData, post, errors } = useForm({
    name: '',
    type: 'invoice',
    content: '',
    is_default: false,
  });

  const [loadingTemplate, setLoadingTemplate] = useState(false);

  const loadStarterTemplate = async () => {
    try {
      setLoadingTemplate(true);
      const res = await fetch(`/templates/${data.type}.html`);
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
  function showOutput() {
    const html = tinymce.get("editor").getContent();
    document.getElementById("htmlOutput").textContent = html;
    document.getElementById("renderedOutput").innerHTML = html;
  }
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
              value={data.type}
              onChange={e => setData('type', e.target.value)}
            >
              <option value="invoice">Invoice</option>
              <option value="quote">Quote</option>
              <option value="proforma">Proforma</option>
              <option value="receipt">Receipt</option>
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
          value={data.content}
          handleChange={(field, val) => setData(field, val)}
          errors={errors}
        />

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
