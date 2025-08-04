import { useEffect, useRef } from 'react';

export default function InputRichText({ title, field, value, mode, handleChange, errors }) {
  const editorRef = useRef(null);
  const isInitialized = useRef(false); // prevent repeated init

  useEffect(() => {
    if (!window.tinymce || !editorRef.current || isInitialized.current) return;

    isInitialized.current = true;

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
        editor.on('init', () => {
          editor.setContent(value || '');
        });
        editor.on('change keyup', () => {
            console.log('field ', field);
          handleChange(field, editor.getContent());
        });
      },
      style_formats: [
        { title: 'Heading 1', block: 'h1' },
        { title: 'Heading 2', block: 'h2' },
        { title: 'Paragraph', block: 'p' },
        { title: 'Arial', inline: 'span', styles: { 'font-family': 'Arial' } },
        { title: 'Montserrat', inline: 'span', styles: { 'font-family': 'Montserrat, sans-serif' } },
        { title: 'Courier New', inline: 'span', styles: { 'font-family': '"Courier New"' } },
        { title: 'Small text', inline: 'span', styles: { 'font-size': '12px' } },
        { title: 'Large text', inline: 'span', styles: { 'font-size': '24px' } },
      ],
    });

    return () => {
      if (window.tinymce) window.tinymce.remove();
    };
  }, []); // no value or mode in dependency array

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
