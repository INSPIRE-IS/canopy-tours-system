import { useEffect, useRef } from 'react';

export default function InputRichText2({ title, name, value, mode, onChange, errors }) {
  const editorRef = useRef(null);
  const isInitialized = useRef(false); // prevent repeated init

  useEffect(() => {
    if (!window.tinymce || !editorRef.current || isInitialized.current) return;

    isInitialized.current = true;

    window.tinymce.init({
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
          onChange= {onChange}; // name, editor.getContent()
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
      <label htmlFor={name} className="block input-header">
        {title}
      </label>
      <textarea ref={editorRef} id={name} className="hidden" />
      {errors[name] && <p className="text-red-500 text-sm mt-2">{errors[name]}</p>}
    </div>
  );
}
