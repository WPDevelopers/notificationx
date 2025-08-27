import React, { useEffect, useState } from 'react'
import { Editor as Wysiwyg } from "react-draft-wysiwyg";
import { EditorState, convertToRaw, ContentState } from "draft-js";
import draftToHtml from 'draftjs-to-html';
import htmlToDraft from 'html-to-draftjs';

import "react-draft-wysiwyg/dist/react-draft-wysiwyg.css";
import { withLabel } from 'quickbuilder';

const toolbarOptions = {
    options: ["inline", "blockType", "textAlign", "colorPicker", "link", 'emoji'],
    inline: {
        options: ["bold", "italic", "underline", "strikethrough", "monospace"],
    },
    blockType: {
        inDropdown: true,
        options: ["Normal", "H1", "H2", "H3", "H4", "H5", "H6", "Blockquote", "Code"],
    },
};

const NxEditor = (props) => {
    const [editorState, setEditorState] = useState(EditorState.createEmpty());
    
    useEffect(() => {
        if (props.value) {
            const { contentBlocks, entityMap } = htmlToDraft(props.value);
            const contentState = ContentState.createFromBlockArray(contentBlocks, entityMap);
            const newContentState = contentState;

            const currentContent = editorState.getCurrentContent();
            const currentHtml = draftToHtml(convertToRaw(currentContent));

            if (props.value !== currentHtml) {
                setEditorState(prevState =>
                    EditorState.push(prevState, newContentState, 'insert-characters')
                );
            }
        }
    }, [props.value]);

    // 🔹 Send updates to parent
    useEffect(() => {
        const currentContent = editorState.getCurrentContent();
        const html = draftToHtml(convertToRaw(currentContent));
        if (html !== props.value) {
            props.onChange?.({
                target: {
                    type: 'editor',
                    value: html,
                    name: props.name
                }
            });
        }
    }, [editorState]);

    return (
        <Wysiwyg
            placeholder={props?.placeholder}
            toolbar={toolbarOptions}
            editorState={editorState}
            toolbarClassName="wprf-editor-toolbar"
            wrapperClassName="wprf-editor wprf-control"
            editorClassName="wprf-editor-main"
            onEditorStateChange={setEditorState}
        />
    );
}

export default withLabel(NxEditor);
