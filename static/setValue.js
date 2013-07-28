function setData() {
	value = prompt("Value to enter here","");
	if(!isNaN(value)) {
		if(value > 0 && value < 10) {
			this.innerHTML = '<input type="hidden" name="'+this.id+'" value="'+value+'">' + value;
			document.getElementById('sudokuform').submit();
		}
		else if(value == 0) {
			this.innerHTML = '&nbsp';
			document.getElementById('sudokuform').submit();
		}
		else {
			alert("Not a number between 1 and 9");
		}
	}
	else {
		alert("Not a number");
	}
}

function initialize() {
	for(x = 1; x < 10; x++) {
		for(y = 1; y < 10; y++) {
			document.getElementById('r'+x+'c'+y).addEventListener('click', setData);
		}
	}
}
