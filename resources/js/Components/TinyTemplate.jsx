import { useEffect, useRef } from 'react';

export default function TinyTemplate({ title, field, value, mode, handleChange, errors }) {
  const editorRef = useRef(null);
  const isInitialized = useRef(false);
  const activeEditor = useRef(null); // store tinymce instance

  useEffect(() => {
    if (!window.tinymce || !editorRef.current || isInitialized.current) return;

    isInitialized.current = true;

    window.tinymce.init({
      license_key: 'gpl',
      target: editorRef.current,
      plugins: 'lists link code removeformat table',
      toolbar:
        'undo redo | styles | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link  image table | code | removeformat',
        style_formats: [
          { title: "Heading 1", block: "h1" },
          { title: "Heading 2", block: "h2" },
          { title: "Paragraph", block: "p" },
          { title: "Arial", inline: "span", styles: { "font-family": "Arial" } },
          { title: "Montserrat", inline: "span", styles: { "font-family": "Montserrat, sans-serif" } },
          { title: "Courier New", inline: "span", styles: { "font-family": '"Courier New"' } },
          { title: "Small text", inline: "span", styles: { "font-size": "12px" } },
          { title: "Large text", inline: "span", styles: { "font-size": "24px" } }
        ],
        menubar: false,
        branding: false,
      browser_spellcheck: true,
      contextmenu: false,
        height: 500,
        width: 1000,
        text_patterns: false,
      readonly: mode === 'view',
      setup: (editor) => {
        activeEditor.current = editor;

        editor.on('init', () => {
          editor.setContent(value || '');
        });

        editor.on('change keyup', () => {
          handleChange(field, editor.getContent());
        });
      },
    });

    return () => {
      if (window.tinymce) window.tinymce.remove();
    };
  }, []); // only run init once

  // 👇 NEW: Update editor if parent `value` changes
  useEffect(() => {
    if (activeEditor.current && value !== activeEditor.current.getContent()) {
      activeEditor.current.setContent(value || '');
    }
  }, [value]);

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
