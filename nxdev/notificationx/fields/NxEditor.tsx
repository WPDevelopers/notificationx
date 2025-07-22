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
        options: [
            "Normal",
            "H1",
            "H2",
            "H3",
            "H4",
            "H5",
            "H6",
            "Blockquote",
            "Code",
        ],
        className: undefined,
        component: undefined,
        dropdownClassName: undefined,
    },
    emoji: {
        className: undefined,
        component: undefined,
        popupClassName: undefined,
    },
};

const NxEditor = (props) => {
    const [editorState, setEditorState] = useState(EditorState.createEmpty());
    const [isInitialized, setIsInitialized] = useState(false);

    useEffect(() => {
        if (props.value && !isInitialized) {
            const { contentBlocks, entityMap } = htmlToDraft(props.value);
            const contentState = ContentState.createFromBlockArray(contentBlocks, entityMap);
            const editorState = EditorState.createWithContent(contentState);
            setEditorState(editorState);
            setIsInitialized(true);
        }
    }, [props.value, isInitialized]);

    useEffect(() => {
        // Only call onChange if the editor has been initialized and content actually changed
        if (isInitialized) {
            const currentContent = editorState.getCurrentContent();
            const tempValue = draftToHtml(convertToRaw(currentContent));

            // Avoid calling onChange if the content hasn't actually changed
            if (tempValue !== props.value) {
                props.onChange({
                    target: {
                        type: 'editor',
                        value: tempValue,
                        name: props.name
                    }
                });
            }
        }
    }, [editorState, isInitialized, props.value, props.name, props.onChange]);

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
