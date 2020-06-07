import React from "react";
import ReactDOM from "react-dom";
import {PaymentView} from "../../molecules/payment/PaymentView";

window.onload = () => ReactDOM.render(<PaymentView />, document.getElementById("payment-options"));
