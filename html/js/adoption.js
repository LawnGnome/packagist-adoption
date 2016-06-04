$(function () {
	var package = null;

	var ColourScheme = function (type) {
		this.current = 0;
		this.palette = palette(type, 12);
	};

	ColourScheme.prototype = {
		colour: function () {
			var colour = this.palette[this.current];

			this.current = (this.current + 1) % this.palette.length;

			return "#" + colour;
		},
	};

	var Package = function (name) {
		this.name = name;
	};

	Package.prototype = {
		buildChart: function (data, chart) {
			var datasets = {};
			var dates = null;
			var versions = [];

			$.each(data, function (version, series) {
				if (dates === null) {
					dates = $.map(series, function (_, date) {
						return date;
					});

					dates.sort();

					// Bit of a hack: if there are multiple dates, we'll remove
					// the last one, as the last data point is usually
					// incomplete.
					if (dates.length > 1) {
						dates = dates.slice(0, -1);
					}
				}

				var values = [];
				$.each(dates, function (_, date) {
					values.push(series[date]);
				});

				versions.push(version);
				datasets[version] = values;
			});

			versions.sort();
			var colours = new ColourScheme("tol");

			new Chart(chart, {
				type: "line",
				data: {
					labels: dates,
					datasets: $.map(versions, function (version, _) {
						var colour = colours.colour();

						return {
							backgroundColor: colour,
							borderColor: colour,
							fill: false,
							label: version,
							data: datasets[version],
							pointRadius: 0,
						};
					}),
				},
			});
		},

		buildCharts: function () {
			this.buildChart(this.major, $("#major-chart"));
			this.buildChart(this.minor, $("#minor-chart"));
			this.buildChart(this.versions, $("#all-chart"));
		},

		load: function () {
			var self = this;

			$("#overlay").addClass("loading");
			$.getJSON("/" + escape(this.name)).done(function (data) {
				self.major = data.major;
				self.minor = data.minor;
				self.time = data.time;
				self.versions = data.versions;

				self.buildCharts();
				$("#overlay").removeClass("loading");
			}).fail(function () {
				$("#package").parent(".form-group").addClass("has-error");
				$("#overlay").removeClass("loading");
			});
		},
	};

	var updateFromLocation = function () {
		package = new Package(window.location.hash.slice(1));
		package.load();
		document.getElementById("package-name").innerText = package.name;
	};

	$(window).on("hashchange", updateFromLocation);

	$("#package-form").submit(function (event) {
		event.preventDefault();
		$("#package").parent(".form-group").removeClass("has-error");
		window.location = "#" + $("#package").val();
	});

	if (window.location.hash && window.location.hash.length > 1) {
		$("#package").val(window.location.hash.slice(1));
		updateFromLocation();
	}
});

// vim: set noet ts=4 sw=4:
