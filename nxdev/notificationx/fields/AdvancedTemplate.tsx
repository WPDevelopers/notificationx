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
        // generating template for first time.

        if (!builderContext.savedValues?.["advanced_template"]) {
            const theme = builderContext.values.themes;
            let values = {...builderContext.values};
            console.log(theme, values);

            if(theme == 'page_analytics_pa-theme-two' || theme == 'page_analytics_pa-theme-one'){
                const fifth = values['notification-template'].ga_fifth_param?.trim();
                const sixth = values['notification-template'].sixth_param?.replace('tag_', '');
                const custom = `{{${sixth}:${fifth}}}`;
                values = {...values, 'notification-template': {
                    ...values['notification-template'],
                    ga_fifth_param: custom,
                    sixth_param: '',
                }};
            }
            const tmpl: any = applyFilters(
                "nx_adv_template_default",
                values
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
            {props?.sales_count_themes?.includes(
                builderContext.values.themes
            ) && <div className="template-example">
                <span className="advance-template-label">Example: {` `}</span>
                <code>{`{{title}} {{7days}}`}</code><span className="advance-template-doc">{` or `}</span>
                <code>{`{{title}} in last {{day:7}}`}</code>
                <span className="advance-template-doc">. {' '}For more information check out this <a href="https://notificationx.com/docs/notificationx-advanced-template/" target="_blank">doc</a>.</span>
            </div>}
            {(builderContext.values.themes == 'page_analytics_pa-theme-one' || builderContext.values.themes == 'page_analytics_pa-theme-two') &&
            <div className="template-example">
                <span className="advance-template-label">Example: {` `}</span>
                <code>{`in last {{day:7}}`}</code>
                <span className="advance-template-doc">. {' '}For more information check out this <a href="https://notificationx.com/docs/notificationx-advanced-template/" target="_blank">doc</a>.</span>
            </div>
            }
            <br />
            <div className="template-options">
            <span className="advance-template-label">Variables:</span>
                {builderContext
                    .eligibleOptions(templateOptions)
                    .map((val, i) => {
                        if (
                            val.value != "tag_day" &&
                            val.value != "tag_month" &&
                            val.value != "tag_year" &&
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
                            title="7 days"
                            onClick={() => clicked("day:7")}
                        >
                            {`{{day:7}}`}
                        </span>
                    </React.Fragment>
                )}
                {(builderContext.values.themes == 'page_analytics_pa-theme-one' || builderContext.values.themes == 'page_analytics_pa-theme-two') && (
                    <>
                    <React.Fragment key="day">
                        <span
                            className="button button-secondary"
                            title="{{day:7}}"
                            onClick={() => clicked("day:7")}
                        >
                            {`{{day:7}}`}
                        </span>
                    </React.Fragment>
                    <React.Fragment key="month">
                        <span
                            className="button button-secondary"
                            title="{{month:7}}"
                            onClick={() => clicked("month:7")}
                        >
                            {`{{month:7}}`}
                        </span>
                    </React.Fragment>
                    <React.Fragment key="year">
                        <span
                            className="button button-secondary"
                            title="{{year:7}}"
                            onClick={() => clicked("year:7")}
                        >
                            {`{{year:7}}`}
                        </span>
                    </React.Fragment>
                    </>
                )}
            </div>
        </>
    );
};

export default AdvancedTemplate;
