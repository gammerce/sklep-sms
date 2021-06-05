import React, { FunctionComponent, useState } from "react";
import AsyncSelect from "react-select/async";
import { Controlled as CodeMirror } from "react-codemirror2";
import "codemirror/mode/htmlmixed/htmlmixed";

export const ThemeView: FunctionComponent = () => {
    const [templateContent, setTemplateContent] = useState<string>();
    const [templateName, setTemplateName] = useState<string>();

    // TODO Don't allow to change template when there are changes in content
    // TODO Provide the ability to save changes
    // TODO Provide the ability to reset changes

    const loadTemplateList = (inputValue, callback) => {
        // TODO Load data from backend
        setTimeout(() => {
            callback([{ label: inputValue, value: inputValue }]);
        }, 1000);
    };

    const loadTemplateContent = (name: string) => {
        // TODO Add loader
        setTimeout(() => {
            setTemplateContent(`
            <html>
            <head></head>
            <body>Test</body>
</html>`);
        }, 1000);
    };

    const handleTemplateNameChange = (e) => {
        setTemplateName(e.value);
        loadTemplateContent(e.value);
    };
    const handleTemplateContentChange = (editor, data, value) => setTemplateContent(value);

    return (
        <div>
            <div className="field">
                <div className="control">
                    <AsyncSelect
                        className="theme-selector"
                        cacheOptions
                        loadOptions={loadTemplateList}
                        defaultOptions
                        onChange={handleTemplateNameChange}
                    />
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
        </div>
    );
};
