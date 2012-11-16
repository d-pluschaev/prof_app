#!/bin/sh

chmod -R 0777 ./

cp ./config.php.sample ./config.php
cp ./config_dynamic.srz.sample ./config_dynamic.srz
cp ./auth.txt.sample ./auth.txt
cp ./data/maintain_agent_files/auto_prepend_conf.srz.sample ./data/maintain_agent_files/auto_prepend_conf.srz
