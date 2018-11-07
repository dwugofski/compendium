
import { Markup } from "./markup.js";

const e = React.createElement;

const initialValue = Slate.Value.fromJSON({
  document: {
    nodes: [
      {
        object: 'block',
        type: 'paragraph',
        nodes: [
          {
            object: 'text',
            leaves: [
              {
                text: 'A line of text in a paragraph.',
              },
            ],
          },
        ],
      },
    ],
  },
})

export class CompEditor extends React.Component {
	constructor() {
		super();
		this.state = {value: "#hello\n\nhello hello 2u	80ud-ca8mndm-08acsd7qm3-c4t\nhello hello  \nhelloh sdahfopsdojf\n\nheelsafdjpi"};
	}

	onChange(event) {
		const value = event.target.value;
		const mkup = new Markup(value);
		console.log(mkup.parse());
		this.setState({value: value});
	}

	onKeyDown(event) {
		return;
		console.log(this.props);
	}

	render() {
		return e(
			SlateReact.Editor,
			{	value: initialValue,
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