// resources/js/Components/InvoiceTemplateEditor.jsx
import { useEffect, useState } from 'react';
import DynamicVariableSelector from './DynamicVariableSelector';
import TinyTemplate from './TinyTemplate';

export default function InvoiceTemplateEditor({ value, handleChange, errors }) {
  const [content, setContent] = useState('');

  useEffect(() => {
    setContent(value || '');
  }, [value]);

  const handleInsert = (variable) => {
    if (window.tinymce.activeEditor) {
      window.tinymce.activeEditor.insertContent(variable);
    } else {
      handleChange('content', (value || '') + variable);
    }
  };

  return (
    <div>
      <DynamicVariableSelector onInsert={handleInsert} />

      <TinyTemplate
        title="Template Body"
        field="content"
        value={content}
        mode="edit"
        handleChange={handleChange}
        errors={errors}
      />
    </div>
  );
}
