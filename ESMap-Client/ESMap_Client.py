import ClientConfig

print("Loading config...")
c = ClientConfig.load()

print("Test value: " + c.TestValue)

print("Changing test value...")
c.TestValue = c.TestValue + '+'

print("Test value: " + c.TestValue)

print("Saving config...")
ClientConfig.save(c)
