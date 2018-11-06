
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
})

class CompEditor extends React.Component {
	render() {
		return <CompEditor value={this.state.value} onChange={this.onChange} />;
	}
}

ReactDOM.render(
	<CompEditor />,
	document.getElementById('page_form_text_div')
);