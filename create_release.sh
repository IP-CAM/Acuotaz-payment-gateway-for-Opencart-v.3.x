 
#!/bin/bash

# This name *must* match with the git folder
PLUGIN_NAME=opencart-apurata-payment-gateway

(
	zip -r ${PLUGIN_NAME}.ocmod.zip upload/ install.xml
)