import { BrowserProvider } from "ethers";

const provider = new BrowserProvider(window.ethereum);

const BACKEND_ROOT_URI = process.env.BACKEND_ROOT_URI;

async function createMessage(address, nonce) {
  const message = `I own address: ${address}\nrequest: ${nonce}`;
  return message;
}

function connectWallet() {
  provider
    .send("eth_requestAccounts", [])
    .catch(() => console.log("user rejected request"));
}

async function signInWithEthereum() {
  const signer = await provider.getSigner();

  const nonce_res = await fetch(`${BACKEND_ROOT_URI}/nonce`, {
    credentials: "include",
  });
  const nonce = await nonce_res.text();
  if (!nonce) {
    throw new Error("Error while fetching nonce from server!");
  }
  const address = await signer.getAddress();
  const message = await createMessage(address, nonce);
  const signature = await signer.signMessage(message);

  const res = await fetch(`${BACKEND_ROOT_URI}/verify`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ address, signature, nonce }),
    credentials: "include",
  });

  console.log(await res.text());
}

function updateConnectWalletButton() {
    if (window.ethereum && window.ethereum.selectedAddress) {
      // Wallet is already connected, disable the button
      connectWalletBtn.disabled = true;
    } else {
      // Wallet is not connected, enable the button
      connectWalletBtn.disabled = false;
    }
  }

const connectWalletBtn = document.getElementById("connectWalletBtn");
const siweBtn = document.getElementById("siweBtn");
connectWalletBtn.onclick = connectWallet;
siweBtn.onclick = signInWithEthereum;

// Call the updateConnectWalletButton function when the page loads
updateConnectWalletButton();

//Event listener
window.ethereum.on("accountsChanged", (accounts) => {
  if (accounts.length > 0) {
    connectWalletBtn.disabled = true;
  } else {
    connectWalletBtn.disabled = false;
  }
});
