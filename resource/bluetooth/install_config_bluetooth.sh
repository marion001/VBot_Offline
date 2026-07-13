#!/bin/bash

sudo sed -i '/^\[General\]/,/^\[/ {
    s|^[[:space:]]*#\?[[:space:]]*ControllerMode[[:space:]]*=.*|ControllerMode = dual|
    s|^[[:space:]]*#\?[[:space:]]*FastConnectable[[:space:]]*=.*|FastConnectable = true|
    s|^[[:space:]]*#\?[[:space:]]*JustWorksRepairing[[:space:]]*=.*|JustWorksRepairing = confirm|
    s|^[[:space:]]*#\?[[:space:]]*RefreshDiscovery[[:space:]]*=.*|RefreshDiscovery = true|
    s|^[[:space:]]*#\?[[:space:]]*SecureConnections[[:space:]]*=.*|SecureConnections = on|
}' /etc/bluetooth/main.conf

sudo sed -i '/^\[Policy\]/,/^\[/ {
    s|^[[:space:]]*#\?[[:space:]]*ReconnectAttempts[[:space:]]*=.*|ReconnectAttempts = 20|
    s|^[[:space:]]*#\?[[:space:]]*ReconnectIntervals[[:space:]]*=.*|ReconnectIntervals = 1,2,3,5,8|
    s|^[[:space:]]*#\?[[:space:]]*AutoEnable[[:space:]]*=.*|AutoEnable = true|
}' /etc/bluetooth/main.conf

sudo systemctl restart bluetooth