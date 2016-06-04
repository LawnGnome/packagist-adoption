document.addEventListener("DOMContentLoaded", function () {
	document.getElementById("package-form").addEventListener("submit", function (event) {
		var package = document.getElementById("package").value;

		event.preventDefault();

		document.getElementById("package-name").innerText = package;
	});
});

// vim: set noet ts=4 sw=4:
