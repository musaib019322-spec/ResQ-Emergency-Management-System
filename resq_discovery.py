
import socket

DISCOVERY_PORT = 4210
DISCOVERY_REQUEST = b"RESQ_DISCOVER"
DISCOVERY_RESPONSE = b"RESQ_SERVER"

server = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
server.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
server.bind(("0.0.0.0", DISCOVERY_PORT))

print("====================================")
print("ResQ XAMPP Discovery Service")
print("Listening on UDP port:", DISCOVERY_PORT)
print("Keep this window open while using ResQ.")
print("====================================")

while True:
    data, esp32_address = server.recvfrom(1024)

    if data.strip() != DISCOVERY_REQUEST:
        continue

    route = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)

    try:
        route.connect((esp32_address[0], 9))
        laptop_ip = route.getsockname()[0]
    except OSError as error:
        print("Could not determine laptop IP:", error)
        continue
    finally:
        route.close()

    print("Discovery request from:", esp32_address[0])
    print("Laptop IP:", laptop_ip)

    try:
        server.sendto(DISCOVERY_RESPONSE, esp32_address)
        print("ResQ server address sent successfully.")
    except OSError as error:
        print("Could not reply to ESP32:", error)
