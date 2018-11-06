
import { Markup } from "./markup.js";

const e = React.createElement;

export class CompEditor extends React.Component {
	constructor() {
		super();
		this.state = {value: "Hello world!"};
	}

	onChange(event) {
		const value = event.target.value;
		this.setState({value: value});
	}

	onKeyDown(event) {
		return;
		console.log(this.props);
	}

	render() {
		return e(
			"textarea",
			{	value: this.state.value,
				onChange: this.onChange.bind(this),
				onKeyDown: this.onKeyDown.bind(this),
				id: "page_form_text"}
			);
	}
}

ReactDOM.render(
	e(CompEditor),
	$("#page_form_text_div")[0]
);