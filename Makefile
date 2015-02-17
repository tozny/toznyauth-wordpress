all: patch_latest_license patch_latest_http_client
	echo "Done!"

patch_latest_http_client: pull_realm_sdk pull_user_sdk
	git apply http_client.patch	

patch_latest_license: pull_realm_sdk pull_user_sdk
	git apply license.patch

pull_user_sdk:
	curl -O https://raw.githubusercontent.com/tozny/sdk-php/master/ToznyRemoteUserAPI.php

pull_realm_sdk:
	curl -O https://raw.githubusercontent.com/tozny/sdk-php/master/ToznyRemoteRealmAPI.php 
