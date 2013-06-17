// required: jquery.validate.js
//
if (petro == undefined)
{
	var petro = {};
}

(function($, window, document, undefined) {

	petro.table = {

		init: function(elem, options) {
			var self = this;

			self.options = $.extend({}, petro.table.options, options);

			self.getElements(elem);
			self.bindEvents();

			self.initColumns();
			self.initData();

		},

		// get and set all elements needed for script
		getElements: function(elem) {

			var self = this;

			self.table = elem;

			self.$table = $(elem);

			self.$tableHead = self.$table.find('th[col]');

			self.$tableBody = self.$table.find('tbody');

			self.$tableFoot = self.$table.find('tfoot');

			self.$modal = $(self.$table.attr('modal'));

			if (self.options.item_submit == null) {
				self.$item_submit = self.$modal.find('input:submit');
			} else {
				self.$item_submit = $(self.options.item_submit);
			}

			if (self.options.main_form == null) {
				self.$main_form = self.$table.parents('form');
			} else {
				self.$main_form = $(self.options.main_form);
			}

			if (self.options.items_name == '') {
				self.options.items_name = 'items';
			}

			if ($('input[name='+self.options.items_name+']').length < 1) {
				var name = self.options.items_name;
				self.$items_param = $('<input />').attr('type','hidden').attr('name', name).attr('id', name).appendTo(self.$main_form);
			} else {
				self.$items_param = $('input[name='+name+']');
			}
		},

		initColumns: function() {
			var self = this;

			// get columns metadata
			self._columns   = [];
			self._items     = [];
			self._summaries = [];

			self.$tableHead.each(function(index) {
				col = $(this);
				col_name = col.attr('col');
				self._columns[index] = {
					name: col_name,
					label: col.text(),
					visible: col.attr('visible') == 'false' ? false : true,
					align: col.attr('align') ? col.attr('align').toLowerCase() : 'left',
					format: col.attr('format') ? col.attr('format') : null,
					summary: col.attr('summary') ? col.attr('summary').toLowerCase() : null,
					process: col.attr('process') ? col.attr('process') : null,
				};

				// store summary field
				if (null != self._columns[index].summary) {
					self._summaries.push({
						'name': col_name,
						'function': self._columns[index].summary,
						'value': 0,
						'count': 0,
					});
				}

				// set column name to tbody, and tfoot
				self.$table.find('tr td:nth-child('+(index+1)+')').attr('col', col.attr('col'));

				if (self._columns[index].visible != true) {
					self.hide_column(col_name);
				}

				// console.log(index + ') ' + self._columns[index].name + ' => ' + self._columns[index].label);
				// console.log(self._columns[index]);
			});

			console.log('self._columns', self._columns.length, self._columns);
			console.log('self._summaries', self._summaries.length, self._summaries);
		},

		initData: function() {
			var self = this;
			// get number of items from data-count attribute in table body
			var items_count = self.$tableBody.attr('data-count');

			// iterate thru each tbody row, row 0 is header
			for (i=1; i <= items_count; i++) {
				var item = {};
				var td = self.$tableBody.find('tr:nth-child('+i+') td');

				td.each(function(j) {
					if (self._columns[j].name[0] !== '_')
						item[self._columns[j].name] = $(td[j]).text();
				});

				// TODO: remove this when sure that before_insert should not be called during initData
				// if (typeof(self.options.before_insert) == 'function')
				// 	self.options.before_insert(item);

				self._items.push(item);
			};

			self.refresh_table();	// in case, before_insert() modifies any column text
			console.log('_items:', self._items.length, self._items, $(self._items).serializeArray());

		},

		// gather and return items data from the table
		gatherData: function() {
			var self = this;

			var items = self._items;
			var items_count = items.length;

			console.log(items);

			var data =[];

			for (var row in items) {
				var obj = {};
				for (var col in items[row]) {
					obj[col] = items[row][col];
				};

				data.push(obj);
			};

			return data;
		},

		bindEvents: function() {
			var self = this;

			// by default, item form input should be reset after the form is hidden
			// otherwise, the input values will still be shown when the form shows up again.
			self.$modal.on('hidden', function() {
				self.reset_item_form();
			});

			// bind Modal submit event
			self.$item_submit.on('click', function(e) {
				e.preventDefault();

				$form = self.$modal.find('form');
				val = $form.validate().form();

				if (val) {
					var item = {};
					$.each(self._columns, function(index) {

						name = self._columns[index].name;
						$ctrl = $('#'+self.options.prefix+name);

						if (name[0] !== '_' && $ctrl.length > 0) {
							if ($ctrl.attr('type') === 'radio') {
								item[name] = $('#'+self.options.prefix+name+':checked').val();
							} else {
								item[name] = $ctrl.val();
							}
						}
						// console.log('item:', name, item[name]);
					});

					// insert new item into data array
					var edit_id = $('#'+self.options.prefix+'edit_id').val();
					if (edit_id == '')
						self.insert_item(item);
					else
						self.update_item(item);

					// hide item modal dialog
					self.$modal.modal('hide');
				} else {
					console.log('subform validation failed.');
				}
			});

			// must bind the event to table body for the event to work
			// for the given 'td a.edit_item' selector , now or in the future
			self.$tableBody.on('click', 'td a.edit_item', function() {
				var id = $(this).attr('href').replace(/#/, '');
				self.edit_item(id);
			});

			self.$tableBody.on('click', 'td a.delete_item', function() {
				var id = $(this).attr('href').replace(/#/, '');
				self.delete_item(id);
			});

			self.$main_form.validate({
		        debug: true,
		        onsubmit: true,
		        submitHandler: function(form) {
		        	var val_ok = true;
		        	if (typeof(self.options.before_submit) == 'function') {
		        		val_ok = self.options.before_submit(self);
		        	}

		        	if (val_ok) {
		        		$('#alert-box').css('display','none');
		        		// console.log(form);
		        		// do submit
		        		var data = self.gatherData();
		        		console.log(data, $(data).serializeArray(), $.toJSON(data));
		        		self.$items_param.attr('value', $.toJSON(data));
		        		form.submit();
		        	}
		        }
			});
		},

		reset_item_form: function() {
			var self = this;
			var $form = self.$modal.find('form');

			$form.find('.error').removeClass('error');		// remove error highlight
			$form.find('.success').removeClass('success');	// remove success hightlight
			$form.find('span.help-inline[generated=true]').text('');	// remove generated error text
			$form.find('input[type=radio]').removeAttr('checked');	// remove checked attributes
			$form.find('input[type=text],input[type=textarea],select').val('');

			// reset edit_id
			$('#'+self.options.prefix+'edit_id').val('');
		},

		hide_column: function(name) {
			var self = this;
			self.$table.find('th[col='+name+']').css('display', 'none');
			self.$table.find('td[col='+name+']').css('display', 'none');
		},

		show_column: function(name) {
			var self = this;
			self.$table.find('th[col='+name+']').css('display', 'table-cell');
			self.$table.find('td[col='+name+']').css('display', 'table-cell');
		},

		align_column: function(name, align) {
			var self = this;
			self.$table.find('th[col='+name+']').css('text-align', align);
			self.$table.find('td[col='+name+']').css('text-align', align);
		},

		format_column: function(name, format) {
			var self = this;
			if (format.toLowerCase() == 'number') {
				self.$table.find('td[col='+name+']').each(function(i, elem) {
					var val = $(elem).text();
					if (val != '')
						$(elem).text(accounting.formatNumber(val, 2));
				});
			}
		},

		item_form: function(options) {
			var self = this;
			alert('item_form : options = '+options);
			if (options === null)
				self.$modal.modal('toggle');
			else
				self.$modal.modal(options);
		},

		make_item: function(id, item) {
			var self = this;

			var cols = '';
			$.each(self._columns, function(index, elem) {
				// elem = self._columns[index]
				col_name = elem.name;
				switch (col_name) {
					case '_seq':
						cols += '<td col="'+col_name+'">' + (id+1) + '</td>';
						break;
					case '_actions':
				        cols += '<td col="'+col_name+'"><a href="#'+id+'" class="edit_item">Edit</a>&nbsp;|&nbsp;';
				        cols += '<a href="#'+id+'" class="delete_item">Delete</a></td>';
						break;
					default:
						if (col_name[0] != '_' && elem.visible == true)
							cols += '<td col="'+col_name+'">' + item[col_name] + '</td>';
				}
			});

			return cols;
		},

		insert_item: function(item) {
			var self = this;

			if (typeof(self.options.before_insert) == 'function')
				self.options.before_insert(item);

			var new_id = self._items.length;
			var cols = self.make_item(new_id, item);

			self._items.push(item);
			self.$tableBody.attr('data-count', self._items.length);

			if (typeof(self.options.after_insert) == 'function')
				self.options.after_insert(item);

			console.log('new id: ', self._items.length, self._items);
			self.refresh_table();
		},

		update_item: function(item) {
			var self = this;

			if (typeof(self.options.before_update) == 'function')
				self.options.before_update(item);

			var id = parseInt($('#'+self.options.prefix+'edit_id').val());
			var cols = self.make_item(id, item);

			self._items[id] = item;

			if (typeof(self.options.after_update) == 'function')
				self.options.after_update(item);

			console.log('cols: ', id, cols);
			self.refresh_table();
		},

		edit_item: function(id) {
			var self = this;

			item = self._items[id];

	        $.each(item, function(index) {

				console.log('edit_item: ' + id, 'index: ' + index, '"'+item[index]+'"');

	            $ctrl = $('#'+self.options.prefix+index);

	            if ($ctrl.length > 0) {
                    if ($ctrl.attr('type') === 'radio') {
	                    $('input:radio[name='+index+']').filter('[value='+item[index]+']').attr('checked', true);
	                } else {
	                    $ctrl.val(item[index]);
	                }
	            }
	        });

	        $('#'+self.options.prefix+'edit_id').val(id);
	        self.$modal.modal('show');
		},

		delete_item: function(id) {
			var self = this;

			if (typeof(self.options.before_delete) == 'function')
				self.options.before_delete(item);

			self._items.splice(id, 1);
			self.$tableBody.attr('data-count', self._items.length);

			if (typeof(self.options.after_delete) == 'function')
				self.options.after_delete(item);

			console.log('after delete:', id, self._items.length, self._items);
			self.refresh_table();
		},

		refresh_table: function () {
			var self = this;
			self.hide_errors();

			if (typeof(self.options.before_refresh) == 'function')
				self.options.before_refresh();

			// if self._items is empty
			if (self._items.length == 0) {
				$.each(self._summaries, function(j, elem) {
					elem.value = 0;
					elem.count = 0;
				});
			}

			// otherwise,
			for (var i=0; i < self._items.length; i++) {
				item = self._items[i];
				var cols = self.make_item(i, item);

				if (i <= self.options.min_rows) {
					self.$tableBody.find('tr:nth-child('+(i+1)+')').html(cols);
				} else {
					self.$tableBody.find('tr:last-child').after('<tr>' + cols + '</tr>');
				}

				// recalculate summaries (if any)
				$.each(self._summaries, function(j, elem) {
					// elem = self._summaries[j]

					// if this is the fisrt run, reset summary value to zero
					if (i == 0) {
						elem.value = 0;
						elem.count = 0;
					}

					elem.value += +item[elem.name];
					elem.count += 1;
				});
			}

			console.log('recal summaries: ', self._summaries);

			// insert blank rows
			while (i <= self.options.min_rows) {
				self.$tableBody.find('tr:nth-child('+(i+1)+')').html( Array(6+1).join('<td>&nbsp;</td>') );
				i++;
			}

			// update the summary row
			$.each(self._summaries, function(index, elem) {
				// elem = self._summaries[index]
				if (elem.function == 'sum')
					val = elem.value;
				else if (elem.function == 'count')
					val = elem.count;
				else if (elem.function == 'avg')
					val = elem.value / elem.count;

				self.$tableFoot.find('td[col='+elem.name+']').text(val);
			});

			// column alignment and format
			$.each(self._columns, function(index, elem) {
				if (elem.align != 'left')
					self.align_column(elem.name, elem.align);

				if (elem.format != null)
					self.format_column(elem.name, elem.format);
			});

			if (typeof(self.options.after_refresh) == 'function')
				self.options.after_refresh(self);
		},

		show_errors: function(errors) {
			var self = this;
			// locate alert-box or define a new one
	        var div = $('#alert-box');
	        if (div.length == 0) {
	            div = $('<div id="alert-box" class="alert alert-error block-message error" data-alert="alert"><a class="close" data-dismiss="alert" href="#">×</a><p><strong>Please correct the following error(s)</strong></p></div>');
	        } else {
	        	// remove old errors msg if exists
	            div.find('ul').remove();
	        }

	        // construct errors list
	        var err = '';
	        // console.log(errors);
	        $.each(errors, function(key, val) {
	            err += '<li>'+val+'</li>';
	        });

	        div.find('p').after('<ul>'+err+'</ul>');
	        self.$main_form.before(div.css('display','block'));
		},

		hide_errors: function() {
			var self = this;
			var div = $('#alert-box');
			if (div.length > 0)	div.css('display','none');
		}

	}

	petro.table.options = {
		items: [],
		items_name: '',			// name of items to be retrieved by the server
		min_rows: 5,			// minimum rows to display
		prefix: 'form_',		// form control prefix
		before_submit: null,
		item_submit: null,		// subform submit button, e.g. #btn_add_item
		before_insert: null,
		after_insert: null,
		before_update: null,
		after_update: null,
		before_delete: null,
		after_delete: null,
		before_refresh: null,
		after_refresh: null,
	};

	petro.table.col_options = {
		visible: true,
		align: 'left',
		rules: {},
		type: 'text',
		format: '',
		// process: function()
	};

	// petro.table.init($('table'));


})(jQuery, window, document);
