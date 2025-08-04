import { useEffect, useRef } from 'react';

export default function Editor() {
  const editorRef = useRef(null);

  useEffect(() => {
    if (!window.tinymce) return;

    window.tinymce.init({
      target: editorRef.current,
      plugins: 'lists link code',
      toolbar:
        'undo redo | styles | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent',// | link | code
      menubar: false,
      branding: false,
      browser_spellcheck: true,
      contextmenu: false,
      style_formats: [
        { title: 'Heading 1', block: 'h1' },
        { title: 'Heading 2', block: 'h2' },
        { title: 'Paragraph', block: 'p' },
        { title: 'Arial', inline: 'span', styles: { 'font-family': 'Arial' } },
        {
          title: 'Montserrat',
          inline: 'span',
          styles: { 'font-family': 'Montserrat, sans-serif' },
        },
        {
          title: 'Courier New',
          inline: 'span',
          styles: { 'font-family': '"Courier New"' },
        },
        {
          title: 'Small text',
          inline: 'span',
          styles: { 'font-size': '12px' },
        },
        {
          title: 'Large text',
          inline: 'span',
          styles: { 'font-size': '24px' },
        },
      ],
      setup: function (editor) {
        editor.on('init', () => {
          console.log('TinyMCE loaded in React component');
        });
      },
    });

    return () => {
      if (window.tinymce) {
        window.tinymce.remove();
      }
    };
  }, []);

  return (
    <>
      <textarea ref={editorRef} />
    </>
  );
}
