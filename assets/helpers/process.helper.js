export class Process {
	
	static async wait(delay) {
		return new Promise(r => setTimeout(r, delay));
	}
	
}
