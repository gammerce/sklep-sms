import axios from "axios";
import {Api} from "./Api";

export const api = new Api(axios.create());
