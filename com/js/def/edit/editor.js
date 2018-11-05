
import React from '../../slate/react.production.min.js';
import ReactDOM from '../../slate/react-dom.production.min.js';
import { Editor, Value } from '../../slate/slate.js';

const init_val = Value.fromJSON({
	document: {
		nodes: {
			object: 'block',
			type: 'paragraph',
			nodes: [{
				object: 'text',
				leaves: [{
					text: 'Begin writing here...'
				}]
			}]
		}
	}
});

class CompEditor extends React.Component {
	state = {value: init_val};

	onChange = ({value}) => {
		this.setState({value});
	}

	render() {
		return <CompEditor value={this.state.value} onChange={this.onChange} />;
	}
}

ReactDOM.render(
	<CompEditor />,
	document.getElementById('page_form_text_div')
);