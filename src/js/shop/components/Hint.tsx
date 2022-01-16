import React, { FunctionComponent } from "react";

interface Props {
    message: string;
}

export const Hint: FunctionComponent<Props> = (props) => {
    const { message } = props;

    if (!message) {
        return null;
    }

    return (
        <span className="icon" title={message}>
            <i className="fas fa-question-circle"></i>
        </span>
    );
};
