import { useEffect, useRef, useState } from 'react';

export default function InputRichText({ title, field, value, mode, handleChange, errors }) {
  const editorRef = useRef(null);
  const editorInstance = useRef(null);
  const [isReady, setIsReady] = useState(false);

  // Initialize TinyMCE once
  useEffect(() => {
    if (!window.tinymce || !editorRef.current) return;

    window.tinymce.init({
      license_key: 'gpl',
      target: editorRef.current,
      plugins: 'lists link code',
      toolbar:
        'undo redo | styles | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent',
      menubar: false,
      branding: false,
      browser_spellcheck: true,
      contextmenu: false,
      readonly: mode === 'view',
      setup: (editor) => {
        editorInstance.current = editor;

        editor.on('init', () => {
          setIsReady(true);
          editor.setContent(value || ''); // initial load only
        });

        editor.on('change keyup', () => {
          handleChange(field, editor.getContent());
        });
      },
    });

    return () => {
      if (editorInstance.current) {
        editorInstance.current.remove();
        editorInstance.current = null;
        setIsReady(false);
      }
    };
  }, [mode]);

  // Only update content if parent changes it externally (not while typing)
  useEffect(() => {
    if (
      isReady &&
      editorInstance.current &&
      value !== undefined &&
      value !== editorInstance.current.getContent()
    ) {
      editorInstance.current.setContent(value || '');
    }
  }, [value, isReady]);

  return (
    <div className="mb-4 w-full">
      <label htmlFor={field} className="block input-header">
        {title}
      </label>
      <textarea ref={editorRef} id={field} className="hidden" />
      {errors[field] && <p className="text-red-500 text-sm mt-2">{errors[field]}</p>}
    </div>
  );
}
