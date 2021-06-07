import React, { FunctionComponent, useEffect, useState } from "react";
import Select from "react-select";
import Creatable from "react-select/creatable";
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

    const [themeList, setThemeList] = useState<SelectOption[]>();
    const [themeListLoading, setThemeListLoading] = useState<boolean>(true);
    const [selectedTheme, setSelectedTheme] = useState<SelectOption>();

    // TODO Don't allow to change template when there are changes in content
    // TODO Provide the ability to save changes
    // TODO Provide the ability to reset changes

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

    const loadTemplate = async (name: string) => {
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

    const loadThemeList = async () => {
        try {
            setThemeListLoading(true);
            const response = await api.getThemeList();
            const options = response.data.map((theme) => ({
                label: theme.name,
                value: theme.name,
            }));
            setThemeList(options);
        } catch (e) {
            infobox.showError(e.toString());
        } finally {
            setThemeListLoading(false);
        }
    };

    const handleTemplateChange = (selectedOption: SelectOption) => {
        if (selectedOption === null) {
            setSelectedTemplate(undefined);
            setTemplateContent(undefined);
        } else {
            setSelectedTemplate(selectedOption);
            loadTemplate(selectedOption.value).catch(handleError);
        }
    };

    const handleTemplateContentChange = (editor, data, value) => setTemplateContent(value);

    const handleThemeChange = (e) => setSelectedTheme(e);

    useEffect(() => {
        loadThemeList().catch(handleError);
        loadTemplateList().catch(handleError);
    }, []);

    return (
        <>
            <div className="field is-grouped">
                <div className="control" style={{ minWidth: "150px" }}>
                    <Creatable
                        className="theme-selector"
                        defaultValue={{ label: "fusion", value: "fusion" }}
                        options={themeList}
                        value={selectedTheme}
                        onChange={handleThemeChange}
                        isLoading={themeListLoading}
                    />
                </div>

                <div className="control is-expanded">
                    <Select
                        className="template-selector"
                        options={templateList}
                        value={selectedTemplate}
                        onChange={handleTemplateChange}
                        isLoading={templateListLoading}
                        isClearable
                    />
                </div>

                <div className="control">
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
