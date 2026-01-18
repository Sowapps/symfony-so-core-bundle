export class Exception extends Error {
	
	constructor(message, previous = null) {
		super(message);
		
		this.previous = previous;
	}
	
	getMessage() {
		return this.message;
	}
	
}

export class NotImplementedException extends Exception {
	
	constructor(message = "Not implemented, you must implement this feature", previous = null) {
		super(message, previous);
	}
	
}
