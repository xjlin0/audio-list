(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$(function() {
		// Audio file upload handling
		const fileSelectButton = document.querySelector('button[onclick="document.getElementById(\'audio_file_select\').click()"]');
		const audioDataSubmitButton = document.querySelector('input[name="audio_submit"]');
		const statusDiv = document.getElementById('upload_status');

		// Handout file upload handling
		const handoutFileSelectButton = document.querySelector('button[onclick="document.getElementById(\'handout_file_select\').click()"]');
		const handoutStatusDiv = document.getElementById('handout_upload_status');

		// Only run if elements exist (only on audio edit page)
		if (!fileSelectButton || !audioDataSubmitButton || !statusDiv) {
			return;
		}

		const uploadFile = async function() {
			const file = document.getElementById('audio_file_select').files[0];
			const sermonDate = document.querySelector('input[name="sermondate"]').value;
			const year = sermonDate.split('-')[0];
			const audioPreviewTr = document.querySelector('tr#audio-preview');

			// Disable both buttons during upload
			fileSelectButton.disabled = true;

			try {
				// Check if file exists
				const formData = new FormData();
				formData.append('action', 'check_aws_file');
				formData.append('nonce', audioListAjax.nonce);
				formData.append('year', year);
				formData.append('filename', encodeURIComponent(file.name));

				try {
					console.log('Checking file:', {year, filename: file.name});
					const response = await fetch(audioListAjax.ajaxurl, {
						method: 'POST',
						body: formData
					});
					const text = await response.text();  // Get response as text first
					console.log('Raw response:', text);

					let data;
					try {
						data = JSON.parse(text);  // Then parse it
					} catch (e) {
						console.error('JSON parse error:', e);
						throw new Error('Invalid server response format ' + text);
					}

					console.log('Parsed response:', data);

					if (data.success) {
						if (data.data.exists) {
							if (!confirm('File already exists. Do you want to overwrite it?')) {
								statusDiv.textContent = 'Upload aborted (file exists)';
								statusDiv.style.color = 'grey';
								fileSelectButton.disabled = false;
								return data;
							}
						}

						// Upload file
						statusDiv.textContent = 'Uploading...';
						const uploadData = new FormData();
						uploadData.append('action', 'upload_to_aws');
						uploadData.append('nonce', audioListAjax.nonce);
						uploadData.append('year', year);
						uploadData.append('file', file);

						const uploadResponse = await fetch(audioListAjax.ajaxurl, {
							method: 'POST',
							body: uploadData
						});
						const uploadText = await uploadResponse.text();
						console.log('Upload response text:', uploadText);

						const uploadResult = JSON.parse(uploadText);
						console.log('Upload result:', uploadResult);

						if (uploadResult.success) {
							statusDiv.textContent = 'Uploaded successful! Please Submit/Update';
							statusDiv.style.color = 'green';
							fileSelectButton.disabled = false;
							audioPreviewTr.removeAttribute('hidden');
							const audioPreview = audioPreviewTr.querySelector('audio');
							audioPreview.src = uploadResult.data.url;
							audioPreview.load();
							const audioPreviewA = audioPreviewTr.querySelector('a');
							audioPreviewA.href += year;
							return uploadResult;
						} else {
							throw new Error(uploadResult.data || 'Upload failed');
						}
					} else {
						throw new Error(data.data?.message || 'Check failed');
					}
				} catch (error) {
					console.error('Error:', error);
					statusDiv.textContent = 'Upload failed: ' + error.message;
					statusDiv.style.color = 'red';
					// Re-enable buttons on error
					fileSelectButton.disabled = false;
				}
			} catch (error) {
				console.error('Error:', error);
				statusDiv.textContent = 'Upload failed: ' + error.message;
				statusDiv.style.color = 'red';
				// Re-enable buttons on error
				fileSelectButton.disabled = false;
			}
		};

		const uploadHandoutFile = async function() {
			const file = document.getElementById('handout_file_select').files[0];
			const sermonDate = document.querySelector('input[name="sermondate"]').value;
			const year = sermonDate.split('-')[0];

			// Disable button during upload
			handoutFileSelectButton.disabled = true;

			try {
				// Check if file exists
				const formData = new FormData();
				formData.append('action', 'check_aws_file');
				formData.append('nonce', audioListAjax.nonce);
				formData.append('year', year);
				formData.append('filename', encodeURIComponent(file.name));

				try {
					console.log('Checking handout file:', {year, filename: file.name});
					const response = await fetch(audioListAjax.ajaxurl, {
						method: 'POST',
						body: formData
					});
					const text = await response.text();
					console.log('Raw response:', text);

					let data;
					try {
						data = JSON.parse(text);
					} catch (e) {
						console.error('JSON parse error:', e);
						throw new Error('Invalid server response format ' + text);
					}

					console.log('Parsed response:', data);

					if (data.success) {
						if (data.data.exists) {
							if (!confirm('File already exists. Do you want to overwrite it?')) {
								handoutStatusDiv.textContent = 'Upload aborted (file exists)';
								handoutStatusDiv.style.color = 'grey';
								handoutFileSelectButton.disabled = false;
								return data;
							}
						}

						// Upload file
						handoutStatusDiv.textContent = 'Uploading...';
						const uploadData = new FormData();
						uploadData.append('action', 'upload_to_aws');
						uploadData.append('nonce', audioListAjax.nonce);
						uploadData.append('year', year);
						uploadData.append('file', file);

						const uploadResponse = await fetch(audioListAjax.ajaxurl, {
							method: 'POST',
							body: uploadData
						});
						const uploadText = await uploadResponse.text();
						console.log('Handout upload response text:', uploadText);

						const uploadResult = JSON.parse(uploadText);
						console.log('Handout upload result:', uploadResult);

						if (uploadResult.success) {
							handoutStatusDiv.textContent = 'Uploaded successful! Please Submit/Update';
							handoutStatusDiv.style.color = 'green';
							handoutFileSelectButton.disabled = false;
							// Update the link input with the AWS URL
							document.getElementById('handoutfile_select').value = uploadResult.data.url;
							return uploadResult;
						} else {
							throw new Error(uploadResult.data || 'Upload failed');
						}
					} else {
						throw new Error(data.data?.message || 'Check failed');
					}
				} catch (error) {
					console.error('Error:', error);
					handoutStatusDiv.textContent = 'Upload failed: ' + error.message;
					handoutStatusDiv.style.color = 'red';
					handoutFileSelectButton.disabled = false;
				}
			} catch (error) {
				console.error('Error:', error);
				handoutStatusDiv.textContent = 'Upload failed: ' + error.message;
				handoutStatusDiv.style.color = 'red';
				handoutFileSelectButton.disabled = false;
			}
		};

		document.getElementById('audio_file_select').addEventListener('change', function(e) {
			const alphanumericRegex = /^\d{8}[a-z]+\d*\.[a-z0-9]{3}$/;
			const file = e.target.files[0];
			const sermonDate = document.querySelector('input[name="sermondate"]').value;
			const year = sermonDate.split('-')[0];
			const previousFilename = document.getElementById('audiofile_input').value;
			if (file) {
				console.log("file name: ", file.name);
				if (!alphanumericRegex.test(file.name)) {
					alert("Only alphanumeric filenames allowed 檔名不能有中文或空白, 只能是小寫英數字如 YYYYMMDDname.mp3");
					document.getElementById('audio_file_select').value = '';
					return;
				}
				document.getElementById('audiofile_input').value = file.name;
				setTimeout(() => {  // confirm() is a blocking operation stopping the above DOM alteration
					if (year && confirm(`Do you want to ${previousFilename ? 'replace ' + previousFilename + ' and ' : ''}upload the file ${file.name} to ${year} folder?`)) {
						audioDataSubmitButton.disabled = true;
						uploadFile()
							.then((result) => {
								console.log('executed uploadFile(), result: ', result);
							})
							.catch((error) => {
								console.error('An error occurred while calling uploadFile():', error);
							})
							.finally(() => {
								audioDataSubmitButton.disabled = false;
							});
					} else if (previousFilename) {
						document.getElementById('audiofile_input').value = previousFilename;
					} else {
						statusDiv.textContent = 'You chose not to upload the file';
					}
					document.getElementById('audio_file_select').value = '';
				}, 1);
			}
		});

		// Handout file selection handling
		document.getElementById('handout_file_select').addEventListener('change', function(e) {
			const alphanumericRegex = /^\d{8}[a-z]+\d*\.[a-z0-9]{3,4}$/;
			const file = e.target.files[0];
			const sermonDate = document.querySelector('input[name="sermondate"]').value;
			const year = sermonDate.split('-')[0];
			const previousFilename = document.getElementById('handoutfile_select').value;

			if (file) {
				console.log("handout file name: ", file.name);
				if (!alphanumericRegex.test(file.name)) {
					alert("Only alphanumeric filenames allowed 檔名不能有中文或空白, 只能是小寫英數字如 YYYYMMDDname.pdf");
					document.getElementById('handout_file_select').value = '';
					return;
				}

				// Temporarily update the input to show the filename
				document.getElementById('handoutfile_select').value = file.name;

				setTimeout(() => {
					if (year && confirm(`Do you want to ${previousFilename ? 'replace ' + previousFilename + ' and ' : ''}upload the file ${file.name} to ${year} folder?`)) {
						audioDataSubmitButton.disabled = true;
						uploadHandoutFile()
							.then((result) => {
								console.log('executed uploadHandoutFile(), result: ', result);
							})
							.catch((error) => {
								console.error('An error occurred while calling uploadHandoutFile():', error);
							})
							.finally(() => {
								audioDataSubmitButton.disabled = false;
							});
					} else if (previousFilename) {
						document.getElementById('handoutfile_select').value = previousFilename;
					} else {
						handoutStatusDiv.textContent = 'You chose not to upload the file';
					}
					document.getElementById('handout_file_select').value = '';
				}, 1);
			}
		});
	});

})( jQuery );
