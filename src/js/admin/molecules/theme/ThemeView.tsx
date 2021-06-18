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
    const [fetchedTemplateContent, setFetchedTemplateContent] = useState<string>("");
    const [templateContent, setTemplateContent] = useState<string>("");

    const [themeList, setThemeList] = useState<SelectOption[]>();
    const [themeListLoading, setThemeListLoading] = useState<boolean>(true);
    const [selectedTheme, setSelectedTheme] = useState<SelectOption>({
        label: "fusion",
        value: "fusion",
    });

    // TODO Don't allow to change template when there are changes in content
    // TODO Provide the ability to save changes
    // TODO Provide the ability to reset changes

    const loadTemplateList = async () => {
        try {
            setTemplateListLoading(true);
            const response = await api.getTemplateList();
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

    const loadTemplate = async (theme: string, name: string) => {
        try {
            loader.show();
            const response = await api.getTemplate(theme, name);
            setTemplateContent(response.content);
            setFetchedTemplateContent(response.content);
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

    const updateTemplate = async () => {
        try {
            loader.show();
            await api.putTemplate(selectedTheme.value, selectedTemplate.value, templateContent);
            infobox.showSuccess(__("template_updated"));
        } catch (e) {
            infobox.showError(e.toString());
        } finally {
            loader.hide();
        }
    };

    const handleThemeChange = (selectedOption: SelectOption) => {
        setSelectedTheme(selectedOption);
        loadTemplate(selectedOption.value, selectedTemplate.value).catch(handleError);
    };

    const handleTemplateChange = (selectedOption: SelectOption) => {
        if (selectedOption === null) {
            setSelectedTemplate(undefined);
            setTemplateContent("");
        } else {
            setSelectedTemplate(selectedOption);
            loadTemplate(selectedTheme.value, selectedOption.value).catch(handleError);
        }
    };

    const handleTemplateContentChange = (editor, data, value) => setTemplateContent(value);

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
                        placeholder={__("select_template")}
                        onChange={handleTemplateChange}
                        isLoading={templateListLoading}
                        isClearable
                    />
                </div>

                <div className="control">
                    <button
                        className="button is-success"
                        disabled={fetchedTemplateContent === templateContent}
                        onClick={updateTemplate}
                    >
                        {__("save")}
                    </button>
                </div>
            </div>

            <CodeMirror
                value={templateContent}
                options={{
                    mode: "htmlmixed",
                    theme: "material",
                    lineNumbers: true,
                    readOnly: selectedTemplate ? false : "nocursor",
                }}
                onBeforeChange={handleTemplateContentChange}
            />
        </>
    );
};
