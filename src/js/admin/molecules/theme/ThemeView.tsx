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
import { onKeyPress } from "../../../general/effects";

interface SelectOption {
    label: string;
    value: string;
}

interface TemplateSelectOption {
    label: string;
    value: string;
    deletable: boolean;
}

export const ThemeView: FunctionComponent = () => {
    const [templateList, setTemplateList] = useState<SelectOption[]>();
    const [templateListLoading, setTemplateListLoading] = useState<boolean>(true);
    const [selectedTemplate, setSelectedTemplate] = useState<TemplateSelectOption>();
    const [fetchedTemplateContent, setFetchedTemplateContent] = useState<string>("");
    const [templateContent, setTemplateContent] = useState<string>("");

    const [themeList, setThemeList] = useState<SelectOption[]>();
    const [themeListLoading, setThemeListLoading] = useState<boolean>(true);
    const [selectedTheme, setSelectedTheme] = useState<SelectOption>({
        label: "fusion",
        value: "fusion",
    });

    const areChangesUnsaved = fetchedTemplateContent !== templateContent;

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

    const loadTemplateList = async (theme: string) => {
        try {
            setTemplateListLoading(true);
            const response = await api.getTemplateList(theme);
            const options = response.data.map((template) => ({
                label: template.name,
                value: template.name,
                deletable: template.deletable,
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

    const updateTemplate = async () => {
        if (!areChangesUnsaved) {
            return;
        }

        try {
            loader.show();
            await api.putTemplate(selectedTheme.value, selectedTemplate.value, templateContent);
            setFetchedTemplateContent(templateContent);
            infobox.showSuccess(__("template_updated"));
        } catch (e) {
            infobox.showError(e.toString());
        } finally {
            loader.hide();
        }
    };

    const deleteTemplate = async () => {
        const theme = selectedTheme.value;
        const name = selectedTemplate.value;

        if (!confirm(__("delete_template_confirmation", name, theme))) {
            return;
        }

        try {
            loader.show();
            await api.deleteTemplate(theme, name);
            infobox.showSuccess(__("template_deleted"));
        } catch (e) {
            infobox.showError(e.toString());
        } finally {
            loader.hide();
        }
    };

    const handleThemeChange = (selectedOption: SelectOption) => {
        if (areChangesUnsaved && !confirm(__("template_unsaved_changes_confirmation"))) {
            return;
        }

        setSelectedTheme(selectedOption);
    };

    const handleTemplateChange = (selectedOption: TemplateSelectOption) => {
        if (areChangesUnsaved && !confirm(__("template_unsaved_changes_confirmation"))) {
            return;
        }

        if (selectedOption === null) {
            setSelectedTemplate(undefined);
            setTemplateContent("");
            setFetchedTemplateContent("");
        } else {
            setSelectedTemplate(selectedOption);
        }
    };

    const handleTemplateContentChange = (editor, data, value) => setTemplateContent(value);

    useEffect(() => {
        loadThemeList().catch(handleError);
    }, []);

    useEffect(() => {
        loadTemplateList(selectedTheme.value).catch(handleError);
    }, [selectedTheme]);
    useEffect(() => {
        if (selectedTemplate) {
            loadTemplate(selectedTheme.value, selectedTemplate.value).catch(handleError);
        }
    }, [selectedTheme, selectedTemplate]);
    useEffect(() => onKeyPress((e) => (e.ctrlKey || e.metaKey) && e.key == "s", updateTemplate), [
        templateContent,
    ]);

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
                        disabled={!areChangesUnsaved}
                        onClick={updateTemplate}
                    >
                        {__("save")}
                    </button>
                </div>
                <div className="control">
                    <button
                        className="button is-danger"
                        disabled={!selectedTemplate?.deletable}
                        onClick={deleteTemplate}
                    >
                        {__("delete")}
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
