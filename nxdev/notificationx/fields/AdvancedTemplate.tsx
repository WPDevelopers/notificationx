import React, { useEffect, useRef, useState } from "react";
import { Editor as Wysiwyg } from "react-draft-wysiwyg";
import {
    EditorState,
    convertToRaw,
    convertFromRaw,
    ContentState,
    RawDraftContentState,
    Modifier,
    Editor,
} from "draft-js";
import draftToHtml from "draftjs-to-html";
import htmlToDraft from "html-to-draftjs";
import { applyFilters } from "@wordpress/hooks";

import "react-draft-wysiwyg/dist/react-draft-wysiwyg.css";
import { useBuilderContext } from "quickbuilder";

export const toolbarOptions = {
    options: ["inline", "blockType", "textAlign", "colorPicker", "link"],
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
};
const AdvancedTemplate = (props) => {
    const builderContext = useBuilderContext();
    const editor = useRef<{ editor: Editor }>();
    const [editorState, setEditorState] = useState(EditorState.createEmpty());
    const [templateOptions, setTemplateOptions] = useState([]);
    const getField = (arr, name) => {
        if (arr.length) {
            return arr.find((field) => field.name == name)?.fields;
        }
        return [];
    };
    let field = getField(builderContext.tabs, "content_tab");
    field = getField(field, "content");
    field = getField(field, "notification-template");

    useEffect(() => {
        if (props.value) {
            // updating editor from saved value.
            const { contentBlocks, entityMap } = htmlToDraft(props.value);
            const contentState = ContentState.createFromBlockArray(
                contentBlocks,
                entityMap
            );
            const editorState = EditorState.createWithContent(contentState);
            setEditorState(editorState);
        }

        // triggering menu open for Contact Form First field.
        let templateIndex = props.parentIndex;
        templateIndex = [...templateIndex, templateIndex.pop() - 1];
        field[0].menuOpen = true;
        builderContext.setFormField(templateIndex, field);

        let options = field
            .filter((f) => f?.options)
            .map((f) => f?.options)
            .flat();
        setTemplateOptions(options);
    }, []);

    useEffect(() => {
        if (field?.[0]?.options?.length > 0) {
            let options = field
                .filter((f) => f?.options)
                .map((f) => f?.options)
                .flat();
            setTemplateOptions(options);
        }
    }, [field?.[0]?.options]);

    useEffect(() => {
        let tempValue = draftToHtml(
            convertToRaw(editorState.getCurrentContent())
        );
        props.onChange({
            target: {
                type: "advanced-template",
                value: tempValue,
                name: props.name,
            },
        });
    }, [editorState]);

    const updateEditorState = (editorState) => {
        const raw = convertToRaw(editorState.getCurrentContent());
        const newRaw: RawDraftContentState = {
            ...raw,
            blocks: raw.blocks.slice(0, 3),
        };
        const newState = EditorState.createWithContent(convertFromRaw(newRaw));
        setEditorState(newState);
    };
    const handleBeforeInput = (
        chars: string,
        editorState: EditorState,
        eventTimeStamp: number
    ) => {
        const raw = convertToRaw(editorState.getCurrentContent());
        if (raw.blocks.length > 3) {
            return "handled";
        }
    };
    const handleReturn = (e, editorState: EditorState) => {
        const raw = convertToRaw(editorState.getCurrentContent());
        if (raw.blocks.length >= 3) {
            e.preventDefault();
            e.stopPropagation();
            return "handled";
        }
    };
    const handlePastedText = (
        text: string,
        html: string,
        editorState: EditorState
    ) => {
        const raw = convertToRaw(editorState.getCurrentContent());
        const editorLine = raw.blocks.length;
        const clipboardLine = text.split(/\r\n|\r|\n/).length;

        if (editorLine + clipboardLine > 4) {
            return true;
        }
    };

    const clicked = (value) => {
        const contentState = editorState.getCurrentContent();
        const sectionState = editorState.getSelection();
        let nextContentState;
        let nextEditorState = EditorState.createEmpty();
        if (sectionState.isCollapsed()) {
            nextContentState = Modifier.insertText(
                contentState,
                sectionState,
                `{{${value}}}`
            );
        } else {
            nextContentState = Modifier.replaceText(
                contentState,
                sectionState,
                `{{${value}}}`
            );
        }

        nextEditorState = EditorState.push(
            editorState,
            nextContentState,
            "insert-fragment"
        );
        setEditorState(nextEditorState);
        setTimeout(() => {
            editor.current.editor.focus();
        }, 300);
    };

    useEffect(() => {
        if (!builderContext.savedValues?.["advanced_template"]) {
            const tmpl: any = applyFilters(
                "nx_adv_template_default",
                builderContext.values
            );
            const { contentBlocks, entityMap } = htmlToDraft(
                tmpl.map((val) => `<p>${val}</p>`).join("\r\n")
            );
            const contentState = ContentState.createFromBlockArray(
                contentBlocks,
                entityMap
            );
            const editorState = EditorState.createWithContent(contentState);
            setEditorState(editorState);
        }
    }, [
        builderContext.values.themes,
        builderContext.values["notification-template"],
    ]);

    return (
        <>
            <Wysiwyg
                ref={editor}
                toolbar={toolbarOptions}
                editorState={editorState}
                toolbarClassName="wprf-editor-toolbar"
                wrapperClassName="wprf-editor wprf-control"
                editorClassName="wprf-editor-main"
                onEditorStateChange={setEditorState}
                handleBeforeInput={handleBeforeInput}
                handleReturn={handleReturn}
                handlePastedText={handlePastedText}
            />
            <div className="template-options">
                Variables:
                {builderContext
                    .eligibleOptions(templateOptions)
                    .map((val, i) => {
                        if (
                            val.value != "tag_custom" &&
                            val.value != "select_a_tag"
                        ) {
                            const tag = val.value.replace("tag_", "");
                            return (
                                <React.Fragment key={i}>
                                    <span
                                        className="button button-secondary"
                                        title={val.label}
                                        onClick={() => clicked(tag)}
                                    >{`{{${tag}}}`}</span>
                                </React.Fragment>
                            );
                        }
                    })}
                {props?.sales_count_themes?.includes(
                    builderContext.values.themes
                ) && (
                    <React.Fragment key=":days">
                        <span
                            className="button button-secondary"
                            title="{{d:7}}"
                            onClick={() => clicked("d:7")}
                        >
                            {`{{d:7}}`}
                        </span>
                    </React.Fragment>
                )}
            </div>
            {props?.sales_count_themes?.includes(
                builderContext.values.themes
            ) && <div className="template-example">
                Example: {` `}
                <code>in last {`{{d:7}}`} days</code>
            </div>}
        </>
    );
};

export default AdvancedTemplate;
