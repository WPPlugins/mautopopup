/** \brief mAutoPopup JavaScript framework
	\file mautopopup.js
	\version 0.5
	\author Christophe SAUVEUR <christophe\@xhaleera.com>
	
	The mAutoPopup framework gathers all client-side operations.
	This source code is released under the terms of the GNU General Public License version 2.0 or later.
*/

function mAutoPopup (lURL, iniYOff, fs)
{
	this.loadingURL = lURL;
	this.initialYOffset = iniYOff;
	this.followScrolling = fs;
	if (this.followScrolling != 'no')
	{
		if (window.onscroll)
			this.oldOnScroll = window.onscroll;
		window.onscroll = this.UpdateScrolling;
	}
	this.Hide();
}

mAutoPopup.prototype = {
	/** \brief URL of the Loading icon. */
	loadingURL: '',
	
	/** \brief Initial offset of the popup layer. */
	initialYOffset: 0,
	
	/** \brief Scrolling vars. */
	followScrolling: 'no',
	startTopPosition: 0,
	currentTopPosition: 0,
	targetTopPosition: 0,
	updatePositionInterval: 0,
	tweenProgress: 0,
	
	/** \brief Old window.onscroll function. */
	oldOnScroll: 0,	
	
	/** \brief Retrieves the vertical scroll offset of the current page.
		\return a positive integer or zero.
	*/
	GetVScrollOffset : function()
	{
		var iebody = (document.compatMode && document.compatMode != "BackCompat") ? document.documentElement : document.body;
		return document.all ? iebody.scrollTop : pageYOffset;
	},
	
	/** \fn mAutoPopup_Show(url, width, height)
		\brief Shows up the "popup" layer in order to display the fullscale image.
		\param url Target image URL
		\param width Layer width
		\param height Layer height
		\note If \p width or \p height is zero, the corresponding dimension won't be updated unless the browser provides automatically this function.
	*/
	Show : function (url, width, height)
	{
		var mimage = document.getElementById("mautopopup_image");
		var mcontrol = document.getElementById("mautopopup");
		if (!mimage || !mcontrol)
			return;
	
		mimage.src = this.loadingURL;
		this.currentTopPosition = this.initialYOffset + this.GetVScrollOffset();
		this.targetTopPosition = this.currentTopPosition;
		mcontrol.style.top = new String(this.currentTopPosition).concat("px");
		if (width != 0)
			mcontrol.style.width = new String(width).concat("px");
		if (height != 0)
			mcontrol.style.height = new String(height).concat("px");
		mcontrol.style.visibility = "visible";
		mimage.src = url;
	},

	/** \brief Hides the "popup" layer".
	*/
	Hide : function ()
	{
		var mcontrol = document.getElementById("mautopopup");
		if (!mcontrol)
			return;
		mcontrol.style.visibility = "hidden";
	},

	/** \brief Updates the "popup" layer position if applying.
	*/
	UpdateScrolling : function ()
	{
		// Old "onscroll" function
		if (mAutoP.oldOnScroll)
			mAutoP.oldOnScroll();
		
		if (mAutoP.followScrolling != 'no')
		{
			if (mAutoP.updatePositionInterval != 0)
			{
				clearInterval(mAutoP.updatePositionInterval);
				mAutoP.updatePositionInterval = 0;
			}
			mAutoP.tweenProgress = 0;
			mAutoP.startTopPosition = mAutoP.currentTopPosition;
			mAutoP.targetTopPosition = mAutoP.initialYOffset + mAutoP.GetVScrollOffset();
			if (mAutoP.followScrolling == 'immediate')
				mAutoP.UpdatePosition();
			else
				mAutoP.updatePositionInterval = setInterval(mAutoP.UpdatePosition, 40);
		}
	},
	
	UpdatePosition : function()
	{
		var mcontrol = document.getElementById("mautopopup");
		if (!mcontrol)
			return;
		
		if (mAutoP.followScrolling == 'immediate')
			mcontrol.style.top = new String(mAutoP.targetTopPosition).concat("px");
		else if (mAutoP.followScrolling == 'smooth')
		{
			mAutoP.tweenProgress += 0.080;
			if (mAutoP.tweenProgress > 1.0)
				mAutoP.tweenProgress = 1.0;
			movement = (mAutoP.targetTopPosition - mAutoP.startTopPosition) * mAutoP.tweenProgress;
			mAutoP.currentTopPosition = Math.round(mAutoP.startTopPosition + movement);
			
			mcontrol.style.top = new String(mAutoP.currentTopPosition).concat("px");
			
			if (mAutoP.tweenProgress >= 1.0)
			{
				clearInterval(mAutoP.updatePositionInterval);
				mAutoP.updatePositionInterval = 0;
			}
		}
	}
};
