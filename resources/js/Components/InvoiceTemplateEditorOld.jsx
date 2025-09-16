// resources/js/Components/InvoiceTemplateEditor.jsx
import { useState } from 'react';
import InputRichText from './InputRichText';
import DynamicVariableSelector from './DynamicVariableSelector';

export default function InvoiceTemplateEditorOld({ value, handleChange, errors }) {
  const [content, setContent] = useState(value || '');

  const handleInsert = (variable) => {
    // Insert at cursor in TinyMCE
    if (window.tinymce.activeEditor) {
      window.tinymce.activeEditor.insertContent(variable);
    } else {
      setContent((prev) => prev + variable);
      handleChange('content', content + variable);
    }
  };

  return (
    <div>
      <DynamicVariableSelector onInsert={handleInsert} />

      <InputRichText
        title="Template Body"
        field="content"
        value={content}
        mode="edit"
        handleChange={(field, val) => {
          setContent(val);
          handleChange(field, val);
        }}
        errors={errors}
      />
    </div>
  );
}
