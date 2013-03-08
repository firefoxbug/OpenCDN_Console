install:
	install -p -d -m 0755 $(PREFIX)/usr/local/opencdn/conf/
	install -p -d -m 0755 $(PREFIX)/usr/local/opencdn/conf_rsync/
	install -p -d -m 0755 $(PREFIX)/usr/local/opencdn/node_logs/
	install -p -d -m 0755 $(PREFIX)/usr/local/opencdn/ocdn/
	install -p -d -m 0755 $(PREFIX)/usr/local/opencdn/pipe/
	install -p -d -m 0755 $(PREFIX)/usr/local/opencdn/sbin/
	install -p -d -m 0755 $(PREFIX)/var/log/opencdn/
	install -p -m 0755 opencdn.conf $(PREFIX)/usr/local/opencdn/conf/
	install -p -m 0755 do_accesslog $(PREFIX)/usr/local/opencdn/sbin/
	install -p -m 0755 icmp $(PREFIX)/usr/local/opencdn/sbin/
	install -p -m 0755 read_info $(PREFIX)/usr/local/opencdn/sbin/
	install -p -m 0755 rsync_send $(PREFIX)/usr/local/opencdn/sbin/
	install -p -m 0755 syn_node $(PREFIX)/usr/local/opencdn/sbin/
	-\cp -dpr ocdn/* $(PREFIX)/usr/local/opencdn/ocdn/
	-\cp -dpr conf_rsync/* $(PREFIX)/usr/local/opencdn/conf_rsync/
clean:
	@rm -f *.rpm
rpm:
	rpmbuild -bb opencdn-console.spec

.PHONY:rpm
