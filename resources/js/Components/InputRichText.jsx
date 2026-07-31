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
      plugins: 'lists link code paste',
      toolbar:
        'undo redo | styles | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent',
      menubar: false,
      branding: false,
      browser_spellcheck: true,
      contextmenu: 'link lists',
      paste_as_text: true,
      paste_data_images: false,
      readonly: mode === 'view',
      setup: (editor) => {
        editorInstance.current = editor;

        const syncEditorValue = () => {
          handleChange(field, editor.getContent());
        };

        editor.on('init', () => {
          setIsReady(true);
          editor.setContent(value || ''); // initial load only
        });

        editor.on('keydown', (event) => {
          if (event.key !== 'Delete' && event.key !== 'Backspace') {
            return;
          }

          const selectedContent = editor.selection.getContent({ format: 'html' });
          const selectionCollapsed =
            typeof editor.selection.isCollapsed === 'function'
              ? editor.selection.isCollapsed()
              : !selectedContent;

          if (selectionCollapsed && !selectedContent) {
            return;
          }

          event.preventDefault();
          editor.selection.setContent('');
          syncEditorValue();
        });

        editor.on('change keyup input undo redo cut paste', () => {
          syncEditorValue();
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
      {mode !== 'view' && (
        <p className="mt-2 text-xs text-gray-500">
          To paste content, click inside the editor and use Ctrl+V. Browsers block direct clipboard access from editor buttons for security.
        </p>
      )}
      {errors?.[field] && <p className="text-red-500 text-sm mt-2">{errors[field]}</p>}
    </div>
  );
}
