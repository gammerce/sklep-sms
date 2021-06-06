import React, { FunctionComponent, useEffect, useState } from "react";
import Select from "react-select";
import { Controlled as CodeMirror } from "react-codemirror2";
import "codemirror/mode/htmlmixed/htmlmixed";
import { api } from "../../utils/container";
import { handleError } from "../../../shop/utils/utils";
import { loader } from "../../../general/loader";
import { infobox } from "../../../general/infobox";
import { __ } from "../../../general/i18n";

interface SelectOption {
    label: string;
    value: string;
}

export const ThemeView: FunctionComponent = () => {
    const [templateList, setTemplateList] = useState<SelectOption[]>();
    const [templateListLoading, setTemplateListLoading] = useState<boolean>(true);
    const [selectedTemplate, setSelectedTemplate] = useState<SelectOption>();
    const [templateContent, setTemplateContent] = useState<string>();

    // TODO Don't allow to change template when there are changes in content
    // TODO Provide the ability to save changes
    // TODO Provide the ability to reset changes
    // TODO Selectable theme

    const loadTemplateList = async () => {
        try {
            setTemplateListLoading(true);
            const response = await api.getThemeTemplateList();
            const options = response.data.map((template) => ({
                label: template.name,
                value: template.name,
            }));
            setTemplateList(options);
        } catch (e) {
            infobox.showError(e.toString());
        } finally {
            setTemplateListLoading(false);
        }
    };

    const loadTemplateContent = async (name: string) => {
        try {
            loader.show();
            const response = await api.getThemeTemplate(name);
            setTemplateContent(response.content);
        } catch (e) {
            infobox.showError(e.toString());
        } finally {
            loader.hide();
        }
    };

    const handleTemplateNameChange = (e) => {
        setSelectedTemplate(e);
        loadTemplateContent(e.value).catch(handleError);
    };
    const handleTemplateContentChange = (editor, data, value) => setTemplateContent(value);

    useEffect(() => {
        loadTemplateList().catch(handleError);
    }, []);

    return (
        <>
            <div className="columns">
                <div className="column is-two-thirds">
                    <div className="field">
                        <div className="control">
                            <Select
                                className="theme-selector"
                                options={templateList}
                                value={selectedTemplate}
                                onChange={handleTemplateNameChange}
                                isLoading={templateListLoading}
                            />
                        </div>
                    </div>
                </div>

                <div className="column" style={{ textAlign: "right" }}>
                    <button className="button is-success">{__("save")}</button>
                </div>
            </div>

            <CodeMirror
                value={templateContent}
                options={{
                    mode: "htmlmixed",
                    theme: "material",
                    lineNumbers: true,
                }}
                onBeforeChange={handleTemplateContentChange}
            />
        </>
    );
};
